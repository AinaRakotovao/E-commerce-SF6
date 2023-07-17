<?php 



    namespace App\Service;

    use Symfony\Component\Mailer\MailerInterface;
    use Symfony\Bridge\Twig\Mime\TemplatedEmail;

    class SendMailService{
        private $mailer;

        public function __construct(MailerInterface $mailer) {
            $this->mailer = $mailer;
        }

        public function Send(
            string $from,
            string $to,
            string $subject,
            string $template,
            array $context,

        ) : void
        {
            // on va créer le mail 
            $email = (new TemplatedEmail())
                ->from($from)
                ->to($to)
                ->subject($subject)
                ->htmlTemplate("emails/$template.html.twig")
                ->context($context);

            // On envoye le mail 
            $this->mailer->send($email);
        }
    }
?>