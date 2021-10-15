<?php
/**
 * Created by PhpStorm.
 * User: shikhathakur
 * Date: 14.09.2021
 * Time: 20:12
 */

namespace App\Command;

use App\Entity\MailSent;
use App\Repository\CourseRepository;
use App\Repository\EmailMasterTemplateRepository;
use App\Repository\MailSentRepository;
use App\Repository\ParticipantRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Class CourseStartSurveyCommand
 *
 * This commands allow to test if the mail server is correctly configured on all environments
 */
class CourseStartSurveyCommand extends Command
{
    protected static $defaultName = 'app:mail:course-start-survey';
    /**
     * @var MailerInterface
     */
    private MailerInterface $mailer;
    private CourseRepository $courseRepository;
    private ParticipantRepository $participantRepository;
    private MailSentRepository $mailSentRepository;
    private string $appDomain;
    private EmailMasterTemplateRepository $emailMasterTemplateRepository;

    /**
     * CourseStartReminderCommand constructor.
     *
     * @param MailerInterface               $mailer
     * @param CourseRepository              $courseRepository
     * @param ParticipantRepository         $participantRepository
     * @param MailSentRepository            $mailSentRepository
     * @param string                        $appDomain
     * @param EmailMasterTemplateRepository $emailMasterTemplateRepository
     */
    public function __construct(MailerInterface $mailer, CourseRepository $courseRepository, ParticipantRepository $participantRepository, MailSentRepository $mailSentRepository, string $appDomain, EmailMasterTemplateRepository $emailMasterTemplateRepository)
    {
        parent::__construct();
        $this->mailer = $mailer;
        $this->courseRepository = $courseRepository;
        $this->participantRepository = $participantRepository;
        $this->mailSentRepository = $mailSentRepository;
        $this->appDomain = $appDomain;
        $this->emailMasterTemplateRepository = $emailMasterTemplateRepository;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addArgument('days', InputArgument::REQUIRED, 'days to go');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = $input->getArgument('days');
        if (!is_string($days)) {
            throw new \LogicException();
        }
        $courses = $this->courseRepository->getCourseStartForSurveyList($days);
        $emailTemplate = $this->emailMasterTemplateRepository->findOneBy(
            [
                'emailType' => 'course_start_survey',
            ]
        );
        if ($courses) {
            foreach ($courses as $course) {
                $participants = $this->participantRepository->getAllForCourseBooked($course->getId());
                foreach ($participants as $participant) {
                    $participantEmail = $participant->getEmail();
                    if (null === $participantEmail) {
                        continue;
                    }
                    $firstname = $participant->getFirstname();
                    $lastname = $participant->getLastname();
                    $courseName = $participant->getCourse()->getName();
                    $start = $participant->getCourse()->getStartDate()->format('Y-m-d h:i:s');
                    $end = $participant->getCourse()->getEndDate()->format('Y-m-d h:i:s');
                    $mailBody = $emailTemplate->getBody();
                    $surveyLink = '<a target="_blank" href="https://'.$this->appDomain.'/course-survey/'.$participant->getId().'/kursstart">Open Survey</a>';
                    $customFields = ['{firstname}', '{lastname}', '{course}', '{startDate}', '{endDate}', '{surveyLink}'];
                    $customFieldsValue = compact('firstname', 'lastname', 'courseName', 'start', 'end', 'surveyLink');
                    $mailBody = str_replace($customFields, $customFieldsValue, $mailBody ?? 'bewerbeagentur');
                    $email = (new Email())
                        ->from($emailTemplate->getFromEmail())
                        ->to($participantEmail)
                        ->subject($emailTemplate->getSubject())
                        ->html($mailBody);
                    $this->mailer->send($email);
                    $mailSent = new MailSent();
                    $mailSent->setEmail($participantEmail);
                    $mailSent->setEmailType(MailSent::COURSE_START_SURVEY);
                    $mailSent->setParticipant($participant);
                    $mailSent->setSubject($emailTemplate->getSubject());
                    $this->mailSentRepository->persist($mailSent);
                }
                $course->setSurveyStartSent(true);
            }
        }
        $this->courseRepository->flush();

        return 0;
    }
}
