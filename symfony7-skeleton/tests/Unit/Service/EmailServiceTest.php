<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\EmailService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Unit tests for EmailService
 */
class EmailServiceTest extends TestCase
{
    private MailerInterface $mailer;
    private Environment $twig;
    private EmailService $service;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->twig = $this->createMock(Environment::class);

        $this->service = new EmailService(
            $this->mailer,
            $this->twig,
            'noreply@example.com'
        );
    }

    public function testSendWelcomeEmail(): void
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->with('emails/welcome.html.twig', ['username' => 'johndoe'])
            ->willReturn('<html>Welcome email content</html>');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getTo()[0]->getAddress() === 'john@example.com'
                    && str_contains($email->getSubject(), 'Welcome');
            }));

        $this->service->sendWelcomeEmail('john@example.com', 'johndoe');
    }

    public function testSendPasswordResetEmail(): void
    {
        $resetUrl = 'https://example.com/reset/abc123';

        $this->twig->expects($this->once())
            ->method('render')
            ->with('emails/password_reset.html.twig', ['reset_url' => $resetUrl])
            ->willReturn('<html>Password reset email</html>');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getTo()[0]->getAddress() === 'user@example.com'
                    && str_contains($email->getSubject(), 'Password Reset');
            }));

        $this->service->sendPasswordResetEmail('user@example.com', $resetUrl);
    }

    public function testSendPasswordChangedEmail(): void
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->with('emails/password_changed.html.twig', [])
            ->willReturn('<html>Password changed email</html>');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getTo()[0]->getAddress() === 'user@example.com'
                    && str_contains($email->getSubject(), 'Password Changed');
            }));

        $this->service->sendPasswordChangedEmail('user@example.com');
    }

    public function testSendContactFormEmail(): void
    {
        $formData = [
            'name' => 'John Doe',
            'email' => 'sender@example.com',
            'subject' => 'General Inquiry',
            'message' => 'This is a test message',
        ];

        $this->twig->expects($this->once())
            ->method('render')
            ->with('emails/contact_form.html.twig', $formData)
            ->willReturn('<html>Contact form email</html>');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($formData) {
                return $email->getTo()[0]->getAddress() === 'admin@example.com'
                    && str_contains($email->getSubject(), $formData['subject'])
                    && $email->getReplyTo()[0]->getAddress() === $formData['email'];
            }));

        $this->service->sendContactFormEmail('admin@example.com', $formData);
    }

    public function testSendTestEmail(): void
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->with('emails/test.html.twig', [])
            ->willReturn('<html>Test email</html>');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getTo()[0]->getAddress() === 'test@example.com'
                    && str_contains($email->getSubject(), 'Test Email');
            }));

        $this->service->sendTestEmail('test@example.com');
    }

    public function testEmailHasCorrectFromAddress(): void
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html>Content</html>');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getFrom()[0]->getAddress() === 'noreply@example.com';
            }));

        $this->service->sendTestEmail('test@example.com');
    }

    public function testEmailHasHtmlContent(): void
    {
        $htmlContent = '<html><body><h1>Test</h1></body></html>';

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn($htmlContent);

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($htmlContent) {
                return str_contains($email->getHtmlBody(), $htmlContent);
            }));

        $this->service->sendTestEmail('test@example.com');
    }
}
