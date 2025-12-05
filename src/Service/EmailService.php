<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Service for sending emails with template support.
 *
 * Provides a convenient wrapper around Symfony Mailer for sending
 * templated emails with common patterns (welcome emails, password resets, etc.).
 */
class EmailService
{
    /**
     * PHP 8.5: Using final constructor property promotion for immutability.
     */
    public function __construct(
        private final readonly MailerInterface $mailer,
        private final readonly string $fromEmail = 'noreply@example.com',
        private final readonly string $fromName = 'ReSymf CMS',
    ) {
    }

    /**
     * Send a welcome email to a new user.
     *
     * @param string $toEmail The recipient's email address
     * @param string $userName The recipient's name
     *
     * @throws TransportExceptionInterface If sending fails
     */
    public function sendWelcomeEmail(string $toEmail, string $userName): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($toEmail)
            ->subject('Welcome to ' . $this->fromName)
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'userName' => $userName,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send a password reset email.
     *
     * @param string $toEmail The recipient's email address
     * @param string $userName The recipient's name
     * @param string $resetToken The password reset token
     * @param string $resetUrl The password reset URL
     *
     * @throws TransportExceptionInterface If sending fails
     */
    public function sendPasswordResetEmail(
        string $toEmail,
        string $userName,
        string $resetToken,
        string $resetUrl,
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($toEmail)
            ->subject('Password Reset Request')
            ->htmlTemplate('emails/password_reset.html.twig')
            ->context([
                'userName' => $userName,
                'resetToken' => $resetToken,
                'resetUrl' => $resetUrl,
                'expirationTime' => '1 hour',
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send a password changed confirmation email.
     *
     * @param string $toEmail The recipient's email address
     * @param string $userName The recipient's name
     *
     * @throws TransportExceptionInterface If sending fails
     */
    public function sendPasswordChangedEmail(string $toEmail, string $userName): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($toEmail)
            ->subject('Password Changed Successfully')
            ->htmlTemplate('emails/password_changed.html.twig')
            ->context([
                'userName' => $userName,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send a generic notification email.
     *
     * @param string $toEmail The recipient's email address
     * @param string $subject The email subject
     * @param string $templatePath The Twig template path
     * @param array<string, mixed> $context Template context variables
     *
     * @throws TransportExceptionInterface If sending fails
     */
    public function sendNotification(
        string $toEmail,
        string $subject,
        string $templatePath,
        array $context = [],
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($toEmail)
            ->subject($subject)
            ->htmlTemplate($templatePath)
            ->context($context);

        $this->mailer->send($email);
    }

    /**
     * Send a contact form submission notification to admin.
     *
     * @param string $adminEmail The admin's email address
     * @param string $senderName The name of the person who submitted the form
     * @param string $senderEmail The email of the person who submitted the form
     * @param string $message The message content
     *
     * @throws TransportExceptionInterface If sending fails
     */
    public function sendContactFormNotification(
        string $adminEmail,
        string $senderName,
        string $senderEmail,
        string $message,
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->replyTo($senderEmail)
            ->to($adminEmail)
            ->subject('New Contact Form Submission from ' . $senderName)
            ->htmlTemplate('emails/contact_form.html.twig')
            ->context([
                'senderName' => $senderName,
                'senderEmail' => $senderEmail,
                'message' => $message,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send a test email to verify email configuration.
     *
     * @param string $toEmail The recipient's email address
     *
     * @throws TransportExceptionInterface If sending fails
     */
    public function sendTestEmail(string $toEmail): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($toEmail)
            ->subject('Test Email from ' . $this->fromName)
            ->htmlTemplate('emails/test.html.twig')
            ->context([
                'timestamp' => new \DateTimeImmutable(),
            ]);

        $this->mailer->send($email);
    }
}
