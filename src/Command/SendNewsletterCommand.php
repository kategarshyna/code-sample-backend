<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SendNewsletterCommand extends Command
{
    protected static $defaultName = 'send:newsletter';
    protected static string $defaultDescription = 'Sends a newsletter to all active users created during the last week';

    private MailerInterface $mailer;
    private UserRepository $userRepository;

    public function __construct(MailerInterface $mailer, UserRepository $userRepository)
    {
        parent::__construct();

        $this->mailer = $mailer;
        $this->userRepository = $userRepository;
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->note('Started to send newsletter');

        $users = $this->userRepository->findActiveUsersCreatedDuringLastWeek();
        foreach ($users as $user) {
            $this->sendNewsletter($user);
        }

        $io->success('Newsletter sent successfully.');

        return 0;
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendNewsletter($user)
    {
        $email = (new Email())
            ->from('cobbleweb@example.com')
            ->to($user->getEmail())
            ->subject('Your best newsletter')
            ->html('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec id interdumnibh.
            Phasellus blandit tortor in cursus convallis. Praesent et tellus fermentum, pellentesque lectus at,
            tincidunt risus. Quisque in nisl malesuada, aliquet nibh at, molestie libero.</p>');

        $this->mailer->send($email);
    }
}
