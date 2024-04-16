<?php
namespace Budgetcontrol\Authtentication\Service;

use Budgetcontrol\SdkMailer\Domain\Transport\ArubaSmtp;
use Budgetcontrol\SdkMailer\Service\EmailService;
use Symfony\Component\Mailer\Transport\Dsn;
use Budgetcontrol\SdkMailer\View\AuthMail;

class MailService {

    private EmailService $emailService;

    public function __construct()
    {
        $dsn = new Dsn(ENV('MAIL_DRIVER','mailhog'), env('MAIL_HOST'), env('MAIL_USER'), env('MAIL_PASSWORD'));
        $this->emailService = new EmailService($dsn, env('MAIL_FROM_ADDRESS'));
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
}