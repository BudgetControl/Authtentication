<?php
namespace Budgetcontrol\Authentication\Service;

use Budgetcontrol\SdkMailer\Domain\Transport\ArubaSmtp;
use MLAB\SdkMailer\Service\EmailService;
use Symfony\Component\Mailer\Transport\Dsn;
use MLAB\SdkMailer\View\AuthMail;

class MailService {

    private EmailService $emailService;

    public function __construct()
    {
        $mail = new \MLAB\SdkMailer\Service\Mail();
        $mail->setHost(env('MAIL_HOST', 'mailhog'));
        $mail->setDriver(env('MAIL_DRIVER', 'mailhog'));
        $mail->setPassword(env('MAIL_PASSWORD', ''));
        $mail->setUser(env('MAIL_USER', ''));
        $mail->setEmailFromAddress(env('MAIL_FROM_ADDRESS'));

        $this->emailService = $mail;
    }

    public function send_signUpMail(string $to, string $name, string $token)
    {   
        $view = new AuthMail([
            'name' => $name,
            'confirm_link' =>  env('APP_URL', 'http://localhost') . '/app/auth/confirm/' . $token,
        ]);
        $view->sign_upView();

        $this->emailService->sendEmail(
            $to,
            'Sign Up Confirmation',
            $view
        );
    }

    public function send_resetPassowrdMail(string $to, string $name, string $token)
    {
        $view = new AuthMail([
            'link' =>  env('APP_URL', 'http://localhost') . '/app/auth/reset-password/' . $token,
        ]);
        $view->recovery_passwordView();

        $this->emailService->sendEmail(
            $to,
            'Reset Password',
            $view
        );
    }
}