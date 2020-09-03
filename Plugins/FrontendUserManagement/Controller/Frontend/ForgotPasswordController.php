<?php
/**
 * Created by PhpStorm.
 * User: Alexander Wegner
 * Date: 11.02.2019
 * Time: 10:05
 */

namespace FrontendUserManagement\Controller\Frontend;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FrontendUserManagement\Models\User;
use FrontendUserManagement\Services\PasswordResetService;
use FrontendUserManagement\Services\RegistrationService;
use Interop\Container\Exception\ContainerException;
use Oforge\Engine\Modules\Auth\Enums\InvalidPasswordFormatException;
use Oforge\Engine\Modules\Auth\Services\AuthService;
use Oforge\Engine\Modules\Auth\Services\PasswordService;
use Oforge\Engine\Modules\Core\Abstracts\AbstractController;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointAction;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointClass;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\Core\Services\Session\SessionManagementService;
use Oforge\Engine\Modules\Core\Services\TokenService;
use Oforge\Engine\Modules\I18n\Helper\I18N;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;

/**
 * Class ForgotPasswordController
 *
 * @package FrontendUserManagement\Controller\Frontend
 * @EndpointClass(path="/forgot-password", name="frontend_forgot_password", assetScope="Frontend")
 */
class ForgotPasswordController extends AbstractController {

    /**
     * @param Request $request
     * @param Response $response
     * @EndpointAction()
     */
    public function indexAction(Request $request, Response $response) {
        // show the email form for requesting a reset link
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ContainerException
     * @throws ServiceNotFoundException
     * @EndpointAction()
     */
    public function processAction(Request $request, Response $response) {
        /**
         * @var PasswordResetService $passwordResetService
         * @var Router $router
         */
        $passwordResetService = Oforge()->Services()->get('password.reset');
        $router               = Oforge()->Container()->get('router');
        $body                 = $request->getParsedBody();
        $email                = $body['forgot-password__email'];
        $uri                  = $router->pathFor('frontend_forgot_password');

        /**
         * no token was sent
         */
        if (!isset($body['token']) || empty($body['token'])) {
            Oforge()->View()->Flash()->addMessage('warning', I18N::translate('form_invalid_token', [
                'en' => 'The data has been sent from an invalid form.',
                'de' => 'Die Daten wurden von einem ungültigen Formular gesendet.',
            ]));
            Oforge()->Logger()->get()->addWarning('Someone tried to do a backend login with a form without csrf token! Redirecting to backend login.');

            return $response->withRedirect($uri, 302);
        }

        /**
         * invalid token was sent
         */
        /** @var TokenService $tokenService */
        $tokenService = Oforge()->Services()->get('token');
        if (!$tokenService->isValid($body['token'])) {
            Oforge()->View()->Flash()->addMessage('warning', I18N::translate('form_invalid_token', [
                'en' => 'The data has been sent from an invalid form.',
                'de' => 'Die Daten wurden von einem ungültigen Formular gesendet.',
            ]));
            Oforge()->Logger()->get()->addWarning('Someone tried a backend login without a valid form csrf token! Redirecting back to login.');

            return $response->withRedirect($uri, 302);
        }

        /**
         * no email body was sent
         */
        if (!$email) {
            Oforge()->View()->Flash()->addMessage('warning', I18N::translate('form_invalid_data', 'Invalid form data.'));

            return $response->withRedirect($uri, 302);
        }

        /**
         * Email not found
         */
        if (!$passwordResetService->emailExists($email)) {
            Oforge()->View()->Flash()->addMessage('warning', I18N::translate('form_invalid_data', 'Invalid form data.'));
            // Oforge()->View()->Flash()->addMessage('warning', I18N::translate('user_mail_missing', 'Email not found.'));

            return $response->withRedirect($uri, 302);
        }
        $passwordResetLink = $passwordResetService->createPasswordResetLink($email);

        /** @var  RegistrationService $registrationService */
        $registrationService = Oforge()->Services()->get('frontend.user.management.registration');

        // If emailExists == true then the user exists
        /** @var User $user */
        $user = $registrationService->getUser($email);

        $userDetail   = $user->getDetail();
        $userNickName = $userDetail->getNickName();

        $mailService = Oforge()->Services()->get('mail');

        $mailOptions  = [
            'to'       => [$email => $email],
            'from'     => 'no_reply',
            'subject'  => I18N::translate('mailer_subject_password_reset', 'Oforge | Your password reset!'),
            'template' => 'ResetPassword.twig',
        ];
        $templateData = [
            'passwordResetLink' => $passwordResetLink,
            'receiver_name'     => $userNickName,
            'sender_mail'       => $mailService->getSenderAddress('no_reply'),
        ];

        /**
         * Mail could not be sent
         */
        if (!$mailService->send($mailOptions, $templateData)) {
            Oforge()->View()->Flash()->addMessage('error', I18N::translate('password_reset_mail_error', 'The mail to reset your password could not be sent'));
            Oforge()->View()->Flash()->addMessage('error', I18N::translate('password_reset_mail_error', [
                'en' => 'The mail to reset your password could not be sent.',
                'de' => 'Die E-Mail zum Zurücksetzen deines Passworts konnte nicht gesendet werden.',
            ]));

            return $response->withRedirect($uri, 302);
        }

        $uri = $router->pathFor('frontend_login');
        Oforge()->View()->Flash()
                ->addMessage('success', I18N::translate('password_reset_mail_send', 'You will receive an email with your password change information.'));

        return $response->withRedirect($uri, 302);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws ServiceNotFoundException
     * @throws ContainerException
     * @EndpointAction()
     */
    public function resetAction(Request $request, Response $response) {
        // show the reset password form
        /**
         * @var PasswordResetService $passwordResetService
         * @var Router $router
         */
        $passwordResetService = Oforge()->Services()->get('password.reset');
        $router               = Oforge()->Container()->get('router');
        $guid                 = $request->getParam('reset');
        $uri                  = $router->pathFor('frontend_login');

        /**
         * No guid
         */
        if (!$guid) {
            Oforge()->View()->Flash()->addMessage('warning', I18N::translate('password_reset_invalid_link', 'Invalid link.'));

            return $response->withRedirect($uri, 302);
        }

        /**
         * Reset link is not valid
         */
        if (!$passwordResetService->isResetLinkValid($guid)) {
            Oforge()->View()->Flash()->addMessage('warning', I18N::translate('password_reset_invalid_link', 'Invalid link.'));

            return $response->withRedirect($uri, 302);
        }

        Oforge()->View()->assign(['guid' => $guid]);

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ServiceNotFoundException
     * @EndpointAction()
     */
    public function changeAction(Request $request, Response $response) {
        /**
         * @var SessionManagementService $sessionManagementService
         * @var PasswordResetService $passwordResetService
         * @var AuthService $authService
         * @var PasswordService $passwordService
         */
        $sessionManagementService = Oforge()->Services()->get('session.management');
        $passwordResetService     = Oforge()->Services()->get('password.reset');
        $authService              = Oforge()->Services()->get('auth');
        $passwordService          = Oforge()->Services()->get('password');
        $router                   = Oforge()->App()->getContainer()->get('router');
        $body                     = $request->getParsedBody();
        $guid                     = $body['guid'];
        $token                    = $body['token'];
        $password                 = $body['password_change'];
        $passwordConfirm          = $body['password_change_confirm'];
        $uri                      = $router->pathFor('frontend_login');
        $jwt                      = null;
        $user                     = null;

        /**
         * no valid form data found
         */
        if (!$guid || !$token || !$password || !$passwordConfirm) {
            Oforge()->View()->Flash()->addMessage('warning', I18N::translate('form_invalid_data', 'Invalid form data.'));

            return $response->withRedirect($uri, 302);
        }

        /**
         * invalid token was sent
         */
        /** @var TokenService $tokenService */
        $tokenService = Oforge()->Services()->get('token');
        if (!$tokenService->isValid($token)) {
            Oforge()->View()->Flash()->addMessage('warning', I18N::translate('form_invalid_token', [
                'en' => 'The data has been sent from an invalid form.',
                'de' => 'Die Daten wurden von einem ungültigen Formular gesendet.',
            ]));
            Oforge()->Logger()->get()->addWarning('Someone tried a backend login without a valid form csrf token! Redirecting back to login.');

            return $response->withRedirect($uri, 302);
        }

        /**
         * Passwords are not identical
         */
        if ($password !== $passwordConfirm) {
            Oforge()->View()->Flash()->addMessage('warning', I18N::translate('form_password_mismatch', 'Passwords do not match.'));

            return $response->withRedirect($uri, 302);
        }
        try {
            $password = $passwordService->validateFormat($password)->hash($password);
        } catch (InvalidPasswordFormatException $exception) {
            Oforge()->View()->Flash()->addMessage('error', $exception->getMessage());

            return $response->withRedirect($uri, 302);
        }
        $user = $passwordResetService->changePassword($guid, $password);

        /*
         * User not found
         */
        if (!$user) {
            Oforge()->View()->Flash()->addMessage('warning', I18N::translate('user_not_found', 'User not found.'));

            return $response->withRedirect($uri, 302);
        }

        $jwt = $authService->createJWT($user);

        /**
         * $jwt is null if the login credentials are incorrect
         */
        if (!isset($jwt)) {
            Oforge()->View()->Flash()->addMessage('warning', I18N::translate('invalid_login_credentials', 'Invalid login credentials.'));

            return $response->withRedirect($uri, 302);
        }

        $sessionManagementService->regenerateSession();
        $_SESSION['auth'] = $jwt;

        $uri = $router->pathFor('frontend_account_dashboard');
        Oforge()->View()->Flash()->addMessage('success', I18N::translate('password_changed_successfully', [
            'en' => 'You have successfully changed your password. You are now logged in.',
            'de' => 'Dein Password wurde erfolgreich geändert. Du bist nun eingeloggt.',
        ]));

        return $response->withRedirect($uri, 302);
    }

}
