<?php

namespace Insertion\Controller\Frontend;

use Doctrine\DBAL\Schema\View;
use Doctrine\ORM\Event\OnFlushEventArgs;
use FrontendUserManagement\Abstracts\SecureFrontendController;
use FrontendUserManagement\Models\User;
use FrontendUserManagement\Services\FrontendUserService;
use FrontendUserManagement\Services\UserDetailsService;
use FrontendUserManagement\Services\UserService;
use Insertion\Models\InsertionType;
use Insertion\Models\InsertionTypeAttribute;
use Insertion\Services\InsertionBookmarkService;
use Insertion\Services\InsertionCreatorService;
use Insertion\Services\InsertionFeedbackService;
use Insertion\Services\InsertionListService;
use Insertion\Services\InsertionProfileService;
use Insertion\Services\InsertionSearchBookmarkService;
use Insertion\Services\InsertionService;
use Insertion\Services\InsertionTypeService;
use Insertion\Services\InsertionUpdaterService;
use Oforge\Engine\Modules\CMS\Bootstrap;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointAction;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointClass;
use Oforge\Engine\Modules\Core\Helper\StringHelper;
use Oforge\Engine\Modules\I18n\Helper\I18N;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;

/**
 * Class FrontendUsersInsertionController
 *
 * @package Insertion\Controller\Frontend
 * @EndpointClass(path="/account/insertions", name="frontend_account_insertions", assetScope="Frontend")
 */
class FrontendUsersInsertionController extends SecureFrontendController {

    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function indexAction(Request $request, Response $response) {
        /**
         * @var $insertionListService InsertionListService
         */
        $insertionListService = Oforge()->Services()->get("insertion.list");

        /**
         * @var $userService FrontendUserService
         */
        $userService = Oforge()->Services()->get("frontend.user");
        $user        = $userService->getUser();

        $result = ["insertions" => $insertionListService->getUserInsertions($user)];

        Oforge()->View()->assign($result);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function pageAction(Request $request, Response $response) {
        /**
         * @var $insertionListService InsertionListService
         */
        $insertionListService = Oforge()->Services()->get("insertion.list");

        $page = isset($_GET["page"]) ? $_GET["page"] : 1;
        /**
         * @var $userService FrontendUserService
         */
        $userService = Oforge()->Services()->get("frontend.user");
        $user        = $userService->getUser();

        $result = ["insertions" => $insertionListService->getUserInsertions($user, $page)];

        Oforge()->View()->assign($result);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     * @EndpointAction(path="/delete/{id}")
     */
    public function deleteAction(Request $request, Response $response, $args) {
        return $this->modifyInsertion($request, $response, $args, 'delete');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     * @EndpointAction(path="/activate/{id}")
     */
    public function activateAction(Request $request, Response $response, $args) {
        return $this->modifyInsertion($request, $response, $args, 'activate');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     * @EndpointAction(path="/disable/{id}")
     */
    public function disableAction(Request $request, Response $response, $args) {
        /** @var User $user */
        /** @var UserService $frontendUserService */
        $user = Oforge()->View()->get('user');

        /** @var  $userDetailService */
        $userDetailService = Oforge()->Services()->get('frontend.user.management.user.details');
        $userDetails       = $userDetailService->get($user['id']);
        $mailService = Oforge()->Services()->get('mail');
        $mailOptions  = [
            'to'       => [$user['email'] => $user['email']],
            'from'     => 'no_reply',
            'subject'  => I18N::translate('mailer_subject_deactivation_confirm', 'Oforge | Your Deactivation was successful'),
            'template' => 'DeactivationConfirm.twig',
        ];
        $templateData = [
            'receiver_name'     => $userDetails->getNickName(),
            'sender_mail'       => $mailService->getSenderAddress('no_reply'),
        ];

        $mailService->send($mailOptions,$templateData);
        return $this->modifyInsertion($request, $response, $args, 'disable');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @param string $action
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    private function modifyInsertion(Request $request, Response $response, $args, string $action) {
        $id = $args["id"];
        /**
         * @var $service InsertionService
         */
        $service   = Oforge()->Services()->get("insertion");
        $insertion = $service->getInsertionById(intval($id));

        /**
         * @var $userService FrontendUserService
         */
        $userService = Oforge()->Services()->get("frontend.user");
        $user        = $userService->getUser();

        if (!isset($insertion) || $insertion == null) {
            return $response->withRedirect("/404", 301);
        }

        if ($user == null || $insertion->getUser()->getId() != $user->getId()) {
            return $response->withRedirect("/401", 301);
        }

        /**
         * @var $updateService InsertionUpdaterService
         */
        $updateService = Oforge()->Services()->get("insertion.updater");

        switch ($action) {
            case "disable":
                $updateService->deactivate($insertion);
                break;
            case "delete":
                $updateService->delete($insertion);
                break;
            case "activate":
                $updateService->activate($insertion);
                break;
        }

        $refererHeader = $request->getHeader('HTTP_REFERER');

        /** @var Router $router */
        $router = Oforge()->App()->getContainer()->get('router');
        $url    = $router->pathFor('frontend_account_insertions');;
        if (isset($refererHeader) && sizeof($refererHeader) > 0) {
            $url = $refererHeader[0];
        }

        Oforge()->View()->Flash()->addMessage("success", "insertion_" . $action);

        return $response->withRedirect($url, 301);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function bookmarksAction(Request $request, Response $response) {
        /**
         * @var $userService FrontendUserService
         */
        $userService = Oforge()->Services()->get("frontend.user");
        $user        = $userService->getUser();

        /**
         * @var $bookmarkService InsertionBookmarkService
         */
        $bookmarkService = Oforge()->Services()->get("insertion.bookmark");

        $bookmarks = $bookmarkService->list($user);

        Oforge()->View()->assign(["bookmarks" => $bookmarks]);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function searchBookmarksAction(Request $request, Response $response) {
        /**
         * @var $userService FrontendUserService
         */
        $userService = Oforge()->Services()->get("frontend.user");
        $user        = $userService->getUser();

        /**
         * @var $bookmarkService InsertionSearchBookmarkService
         */
        $bookmarkService = Oforge()->Services()->get("insertion.search.bookmark");

        $bookmarks = $bookmarkService->list($user);

        /**
         * @var $typeService InsertionTypeService
         */
        $typeService = Oforge()->Services()->get("insertion.type");

        $types    = $typeService->getInsertionTypeList(100, 0);
        $valueMap = [];
        foreach ($types as $type) {

            /**
             * @var $attribute InsertionTypeAttribute
             */
            foreach ($type->getAttributes() as $attribute) {
                $attributeMap[$attribute->getAttributeKey()->getId()] = [
                    "name" => $attribute->getAttributeKey()->getName(),
                    "top"  => $attribute->isTop(),
                ];

                foreach ($attribute->getAttributeKey()->getValues() as $value) {
                    $valueMap[$value->getId()] = $value->getValue();
                }
            }
        }

        Oforge()->View()->assign(["bookmarks" => $bookmarks, "values" => $valueMap]);

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function toggleBookmarkAction(Request $request, Response $response, $args) {
        $id = $args["insertionId"];
        /**
         * @var $service InsertionService
         */
        $service   = Oforge()->Services()->get("insertion");
        $insertion = $service->getInsertionById(intval($id));

        if (!isset($insertion) || $insertion == null) {
            return $response->withRedirect("/404", 301);
        }

        /**
         * @var $userService FrontendUserService
         */
        $userService = Oforge()->Services()->get("frontend.user");
        $user        = $userService->getUser();

        /**
         * @var $bookmarkService InsertionBookmarkService
         */
        $bookmarkService = Oforge()->Services()->get("insertion.bookmark");

        $bookmarkService->toggle($insertion, $user);

        /** @var Router $router */
        $router = Oforge()->App()->getContainer()->get('router');
        $url    = $router->pathFor('insertions_detail', ["id" => $id]);

        $refererHeader = $request->getHeader('HTTP_REFERER');
        if (isset($refererHeader) && sizeof($refererHeader) > 0) {
            $url = $refererHeader[0];
        }

        return $response->withRedirect($url, 301);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function toggleSearchBookmarkAction(Request $request, Response $response, $args) {
        $id = 2;
        /**
         * @var $service InsertionTypeService
         */
        $service       = Oforge()->Services()->get("insertion.type");
        $insertionType = $service->getInsertionTypeById(intval($id));

        if (!isset($insertionType) || $insertionType == null) {
            return $response->withRedirect("/404", 301);
        }

        /**
         * @var $userService FrontendUserService
         */
        $userService = Oforge()->Services()->get("frontend.user");
        $user        = $userService->getUser();

        /**
         * @var $bookmarkService InsertionSearchBookmarkService
         */
        $bookmarkService = Oforge()->Services()->get("insertion.search.bookmark");

        if ($request->isPost()) {
            $filterData = $_POST["filter"];
            $params     = [];

            if (isset($filterData)) {
                $params = json_decode($filterData, true);
            }

            $bookmarkService->toggle($insertionType, $user, $params);
        }

        $url = $bookmarkService->getUrl($id, $params);

        $refererHeader = $request->getHeader('HTTP_REFERER');
        if (isset($refererHeader) && sizeof($refererHeader) > 0) {
            $url = $refererHeader[0];
        }

        return $response->withRedirect($url, 301);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     * @EndpointAction(path="/removeBookmark/{id}")
     */
    public function removeBookmarkAction(Request $request, Response $response, $args) {
        /**
         * @var $bookmarkService InsertionBookmarkService
         */
        $bookmarkService = Oforge()->Services()->get("insertion.bookmark");
        $id = $args["id"];

        $bookmarkService->remove($id);
        Oforge()->View()->Flash()->addMessage('success', I18n::translate('remove_bookmark_success'));

        /** @var Router $router */
        $router = Oforge()->App()->getContainer()->get('router');
        $url    = $router->pathFor('frontend_account_insertions_bookmarks');

        $refererHeader = $request->getHeader('HTTP_REFERER');
        if (isset($refererHeader) && sizeof($refererHeader) > 0) {
            $url = $refererHeader[0];
        }

        return $response->withRedirect($url, 301);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @EndpointAction(path = "/profile")
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function profileAction(Request $request, Response $response) {
        /**
         * @var $userService FrontendUserService
         */
        $userService = Oforge()->Services()->get("frontend.user");

        $user = $userService->getUser();
        /**
         * @var $service InsertionProfileService
         */
        $service = Oforge()->Services()->get("insertion.profile");

        if ($request->isPost() && $user != null) {
            $service->update($user, $_POST);

            if (isset($_FILES["profile"])) {
                /**
                 * @var UserDetailsService $userDetailsService
                 */

                $userDetailsService = Oforge()->Services()->get('frontend.user.management.user.details');

                $user = $userDetailsService->updateImage($user, $_FILES["profile"]);
            }

        }

        $result = $service->get($user->getId());

        Oforge()->View()->assign(["profile" => $result != null ? $result->toArray() : null, "user" => $user->toArray()]);
    }

    public function initPermissions() {
        $this->ensurePermissions('accountListAction', User::class);
        $this->ensurePermissions('bookmarksAction', User::class);
        $this->ensurePermissions('searchBookmarksAction', User::class);
        $this->ensurePermissions('modifyInsertion', User::class);
        $this->ensurePermissions('disableAction', User::class);
        $this->ensurePermissions('pageAction', User::class);
        $this->ensurePermissions('deleteAction', User::class);
        $this->ensurePermissions('activateAction', User::class);
        $this->ensurePermissions('indexAction', User::class);
        $this->ensurePermissions('toggleBookmarkAction', User::class);
        $this->ensurePermissions('toggleSearchBookmarkAction', User::class);
        $this->ensurePermissions('profileAction', User::class);
        $this->ensurePermissions('removeBookmarkAction', User::class);
    }
}
