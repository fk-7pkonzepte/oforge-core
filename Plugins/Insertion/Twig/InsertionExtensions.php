<?php

namespace Insertion\Twig;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\ORMException;
use FrontendUserManagement\Models\User;
use FrontendUserManagement\Services\FrontendUserService;
use FrontendUserManagement\Services\UserService;
use FrontendUserPageBuilder\Services\FrontendUserPageBuilderService;
use Insertion\Models\Insertion;
use Insertion\Services\AttributeService;
use Insertion\Services\InsertionBookmarkService;
use Insertion\Services\InsertionProfileService;
use Insertion\Services\InsertionSearchBookmarkService;
use Insertion\Services\InsertionService;
use Insertion\Services\InsertionSliderService;
use Insertion\Services\InsertionTypeService;
use Oforge\Engine\Modules\Auth\Services\AuthService;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\CRUD\Enum\CrudDataTypes;
use Oforge\Engine\Modules\I18n\Helper\I18N;
use Oforge\Engine\Modules\TemplateEngine\Extensions\Services\UrlService;
use phpDocumentor\Reflection\Types\Boolean;
use Twig_Extension;
use Twig_ExtensionInterface;
use Twig_Filter;
use Twig_Function;

/**
 * Class AccessExtension
 *
 * @package Oforge\Engine\Modules\TemplateEngine\Extensions\Twig
 */
class InsertionExtensions extends Twig_Extension implements Twig_ExtensionInterface {

    /**
     * @inheritDoc
     */
    public function getFunctions() {
        return [
            new Twig_Function('getInsertionValues', [$this, 'getInsertionValues']),
            new Twig_Function('getInsertionAttribute', [$this, 'getAttribute']),
            new Twig_Function('getInsertionValue', [$this, 'getValue']),
            new Twig_Function('hasBookmark', [$this, 'hasBookmark']),
            new Twig_Function('userHasBookmark', [$this, 'userHasBookmark']),
            new Twig_Function('hasSearchBookmark', [$this, 'hasSearchBookmark']),
            new Twig_Function('getInsertionSliderContent', [$this, 'getInsertionSliderContent']),
            new Twig_Function('getSimilarInsertion', [$this, 'getSimilarInsertion']),
            new Twig_Function('getLatestBlogPostTile', [$this, 'getLatestBlogPostTile']),
            new Twig_Function('getQuickSearch', [$this, 'getQuickSearch']),
            new Twig_Function('getChatPartnerInformation', [$this, 'getChatPartnerInformation']),
            new Twig_Function('numberToRomanNumeral', [$this, 'numberToRomanNumeral']),
        ];
    }

    /** @inheritDoc */
    public function getFilters() {
        return [
            new Twig_Filter('age', [$this, 'getAge'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    /**
     * @param string|null $dateTimeObject
     * @param string $type
     * @param bool $horseYears
     *
     * @return string|null
     * @throws \Exception
     */
    public function getAge(?string $dateTimeObject, string $type = 'dateyear', bool $horseYears = false) : ?string {
        $bday  = DateTime::createFromFormat('Y-m-d', $dateTimeObject);
        $today = new Datetime();

        $diff = $today->diff($bday);
        // don't continue if $diff is not set
        if (!$diff) {
            return '';
        }
        switch($type) {
            // return age in months
            case 'datemonth':
                $suffix = I18N::translate('month_suffix');
                return ($diff->y * 12) + ($diff->m) . ' ' . $suffix;

            case 'dateyear':
                $suffix = I18N::translate('year_suffix');
                // TODO: think about adding type horseyear
                if($horseYears) {
                    // return age in horseyears
                    $age = (int)$today->format('Y') - (int)$bday->format('Y');

                    return $age . ' ' . $suffix;
                }
                else {
                    // return age in years
                    return ($diff->y) . ' ' . $suffix;
                }

            default:
                return '';
        }
    }


    /**
     * @param mixed ...$vars
     *
     * @return mixed|string
     * @throws ORMException
     * @throws ServiceNotFoundException
     */
    public function getInsertionValues(...$vars) {
        $result = '';
        if (count($vars) == 1) {
            /** @var AttributeService $attributeService */
            $attributeService = Oforge()->Services()->get('insertion.attribute');
            $tmp              = $attributeService->getAttribute($vars[0]);
            if (isset($tmp)) {
                $result = $tmp->toArray(3);
            }
        }

        return $result;
    }

    /**
     * @param int|null $insertionID
     *
     * @return bool
     * @throws ORMException
     * @throws ServiceNotFoundException
     */
    public function hasBookmark(?int $insertionID) : bool {
        if ($insertionID === null) {
            return false;
        }
        /** @var $authService AuthService */
        $authService = Oforge()->Services()->get('auth');
        if (isset($_SESSION['auth'])) {
            $user = $authService->decode($_SESSION['auth']);
            if (isset($user) && isset($user['id'])) {
                /** @var InsertionBookmarkService $bookmarkService */
                $bookmarkService = Oforge()->Services()->get("insertion.bookmark");

                return $bookmarkService->hasBookmark($insertionID, $user['id']);
            }
        }

        return false;
    }

    /**
     * @return bool
     * @throws ORMException
     * @throws ServiceNotFoundException
     */
    public function userHasBookmark() {
        /** @var $authService AuthService */
        $authService = Oforge()->Services()->get("auth");
        if (isset($_SESSION["auth"])) {
            $user = $authService->decode($_SESSION["auth"]);
            if (isset($user) && isset($user['id'])) {
                /** @var InsertionBookmarkService $bookmarkService */
                $bookmarkService = Oforge()->Services()->get("insertion.bookmark");

                return $bookmarkService->userHasBookmark($user['id']);
            }
        }

        return false;
    }

    /**
     * @param mixed ...$vars
     *
     * @return boolean
     * @throws ServiceNotFoundException
     * @throws ORMException
     */
    public function hasSearchBookmark(...$vars) {
        if (count($vars) == 2) {
            /** @var $authService AuthService */
            $authService = Oforge()->Services()->get("auth");
            if (isset($_SESSION["auth"])) {
                $user = $authService->decode($_SESSION["auth"]);
                if (isset($user) && isset($user['id'])) {
                    /** @var InsertionSearchBookmarkService $bookmarkService */
                    $bookmarkService = Oforge()->Services()->get("insertion.search.bookmark");

                    $filter = isset($vars[1]) ? $vars[1]: [];
                    return $bookmarkService->hasBookmark($vars[0], $user['id'], $filter);
                }
            }
        }

        return false;
    }

    /**
     * @return array
     * @throws ServiceNotFoundException
     * @throws ORMException
     */
    public function getInsertionSliderContent() {
        /** @var InsertionSliderService $insertionSliderService */
        $insertionSliderService = Oforge()->Services()->get("insertion.slider");
        $insertions             = $insertionSliderService->getRandomInsertions(10);

        return ['insertions' => $insertions];
    }

    /**
     * @param array $vars
     *
     * @return array
     * @throws ORMException
     * @throws ServiceNotFoundException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getSimilarInsertion(...$vars) {
        /** @var InsertionSliderService $insertionSliderService */
        $insertionSliderService = Oforge()->Services()->get("insertion.slider");
        $insertion              = $insertionSliderService->getRandomInsertion(1, $vars[0], $vars[1]);

        return $insertion;
    }

    /**
     * @return array
     * @throws ServiceNotFoundException
     */
    public function getLatestBlogPostTile() {
        $blogService = Oforge()->Services()->get("blog.post");
        $blogPost    = $blogService->getLatestPost();

        return isset($blogPost) ? $blogPost->toArray(3) : [];
    }

    /**
     * @return array
     * @throws ServiceNotFoundException
     * @throws ORMException
     */
    public function getQuickSearch() {
        /** @var InsertionTypeService $insertionTypeService */
        $insertionTypeService = Oforge()->Services()->get('insertion.type');
        $quickSearch          = $insertionTypeService->getQuickSearchInsertions();

        return ['types' => $quickSearch, 'attributes' => $insertionTypeService->getInsertionTypeAttributeMap()];
    }

    /**
     * @param array $vars
     *
     * @return array|bool
     * @throws ORMException
     * @throws ServiceNotFoundException
     */
    public function getChatPartnerInformation(...$vars) {
        if (count($vars) === 2) {
            $imageId = null;
            $title   = null;
            $linkId  = null;
            if ($vars[0] === 'requested') {
                /** @var InsertionService $insertionService */
                $insertionService = Oforge()->Services()->get('insertion');
                /** @var Insertion $insertion */
                $insertion = $insertionService->getInsertionById($vars[1]);
                $linkId    = $vars[1];

                try {
                    $imageId = $insertion->getMedia()[0]->getContent()->getId();
                } catch (\Throwable $e) {
                }
                try {
                    $title = $insertion->getContent()[0]->getTitle();
                } catch (\Throwable $e) {
                }

            } else {
                /** @var UserService $userService */
                $userService = Oforge()->Services()->get('frontend.user.management.user');
                /** @var User $user */
                $user = $userService->getUserById($vars[1]);
                /** @var InsertionProfileService $insertionProfileService */
                $insertionProfileService = Oforge()->Services()->get('insertion.profile');

                try {
                    $imageId = $user->getDetail()->getImage()->getId();
                } catch (\Throwable $e) {
                };
                try {
                    $title = $user->getDetail()->getNickName();
                } catch (\Throwable $e) {
                };
                try {
                    $linkId = $insertionProfileService->get($vars[1])->getId();
                } catch (\Throwable $e) {
                }
            }

            return [
                'imageId' => $imageId,
                'title'   => $title,
                'link'    => $linkId,
            ];
        }

        return false;
    }

    /**
     * @return \Insertion\Models\AttributeKey
     * @throws ServiceNotFoundException
     * @throws ORMException
     */
    public function getAttribute(...$vars) {
        if (count($vars) == 1) {
            /** @var InsertionTypeService $insertionTypeService */
            $insertionTypeService = Oforge()->Services()->get('insertion.type');

            return $insertionTypeService->getAttribute($vars[0]);
        }

        return null;
    }

    /**
     * @return \Insertion\Models\AttributeValue
     * @throws ServiceNotFoundException
     * @throws ORMException
     */
    public function getValue(...$vars) {
        if (count($vars) == 1) {
            /** @var InsertionTypeService $insertionTypeService */
            $insertionTypeService = Oforge()->Services()->get('insertion.type');

            return $insertionTypeService->getValue($vars[0]);
        }

        return null;
    }

    /**
     * @param $number
     *
     * @return string
     */
    public function numberToRomanNumeral($number) {
        $map         = [
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1,
        ];
        $returnValue = '';
        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if ($number >= $int) {
                    $number      -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }

        return $returnValue;
    }
}
