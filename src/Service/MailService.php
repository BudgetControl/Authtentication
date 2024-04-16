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
        $dsn = new Dsn('aruba', env('MAIL_HOST'), env('MAIL_USERNAME'), env('MAIL_PASSWORD'));
        $this->emailService = new EmailService($dsn);
    }

    public function send_signUpMail(string $to, string $name, string $token)
    {   
        $view = new AuthMail([
            'name' => $name,
            'confirm_link' =>  env('APP_URL', 'http://localhost') . '/app/auth/confirm/' . $token,
        ]);

        $this->emailService->send(
            $to,
            'Sign Up Confirmation',
            $view
        );
    }
}