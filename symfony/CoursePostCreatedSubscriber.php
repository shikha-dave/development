<?php
/**
 * Created by PhpStorm.
 * User: shikhathakur
 * Date: 15.09.2021
 * Time: 11:26
 */

namespace App\Listener;

use App\Entity\CourseCalendar;
use App\Event\Course\CoursePostCreatedEvent;
use App\Repository\CalendarStaticEntryRepository;
use App\Repository\CourseCalendarRepository;
use App\Repository\WeekRepository;
use DateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CoursePostCreatedSubscriber
 */
class CoursePostCreatedSubscriber implements EventSubscriberInterface
{
    private CalendarStaticEntryRepository $calendarStaticEntryRepository;
    private CourseCalendarRepository $courseCalendarRepository;
    private WeekRepository $weekRepository;

    /**
     * CoursePostCreatedSubscriber constructor.
     *
     * @param CalendarStaticEntryRepository $calendarStaticEntryRepository
     * @param CourseCalendarRepository      $courseCalendarRepository
     * @param WeekRepository                $weekRepository
     */
    public function __construct(CalendarStaticEntryRepository $calendarStaticEntryRepository, CourseCalendarRepository $courseCalendarRepository, WeekRepository $weekRepository)
    {
        $this->courseCalendarRepository = $courseCalendarRepository;
        $this->calendarStaticEntryRepository = $calendarStaticEntryRepository;
        $this->weekRepository = $weekRepository;
    }
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoursePostCreatedEvent::class => 'onCoursePostCreated',
        ];
    }

    /**
     * @param CoursePostCreatedEvent $event
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    public function onCoursePostCreated(CoursePostCreatedEvent $event): void
    {
        $course = $event->getCourse();
        $startDate = $course->getStartDate();
        $courseStartWeek = (int) $startDate->format("W");
        $courseStartYear = (int) $startDate->format("Y");
        $courseType = $course->getType();
        $calStaticData = $this->calendarStaticEntryRepository->getAllForList($courseType);
        $color = $this->randomColor();
        $course->setColor($color);
        $this->courseCalendarRepository->persist($course);

        /** Insert Week name as  Event **/
        if (null !== $courseType) {
            $weekData = $this->weekRepository->getAllForList($courseType);
            if (!empty($weekData)) {
                foreach ($weekData as $week) {
                    $genStartDate = new DateTime();
                    $calcStaticWeek = (int) $week->getWeekNo();
                    $eventStartWeek = $courseStartWeek+$calcStaticWeek - 1;
                    $genStartDate->setISODate($courseStartYear, $eventStartWeek, 1);
                    $weekStartDate = $genStartDate->format('Y-m-d');
                    $genStartDate->setISODate($courseStartYear, $eventStartWeek, 5);
                    $weekEndDate = $genStartDate->format('Y-m-d');
                    $courseCalendar = new CourseCalendar();
                    $courseCalendar->setCourse($course);
                    $courseCalendar->setCourseName($week->getName());
                    $courseCalendar->setProfessor($week->getName());
                    $courseCalendar->setDetails($week->getName());
                    $courseCalendar->setName($week->getName());
                    $courseCalendar->setColor($color);
                    $courseCalendar->setStart(new DateTime($weekStartDate));
                    $courseCalendar->setEnd(new DateTime($weekEndDate));
                    $this->courseCalendarRepository->persist($courseCalendar);
                }
            }
        }
        if (!empty($calStaticData)) {
            foreach ($calStaticData as $item) {
                $calcStaticDay = (int) $item->getDay();
                $calcStaticWeek = (int) $item->getWeek()->getWeekNo();
                $eventStartWeek = $courseStartWeek+$calcStaticWeek - 1;
                $genStartDate = new DateTime();
                $genStartDate->setISODate($courseStartYear, $eventStartWeek, $calcStaticDay);
                $eventDate = $genStartDate->format('Y-m-d');
                $eventStart = $eventDate.' '.$item->getStart()->format('H:i:s');
                $eventEnd = $eventDate.' '.$item->getEnd()->format('H:i:s');
                $courseCalendar = new CourseCalendar();
                $courseCalendar->setCourse($course);
                $courseCalendar->setCourseName($item->getCourse());
                $courseCalendar->setProfessor($item->getProfessor());
                $courseCalendar->setDetails('Professor: '.$item->getProfessor().', Week: '.$item->getCourse());
                $courseCalendar->setName('Week: '.$item->getCourse());
                $courseCalendar->setColor($color);
                $courseCalendar->setStart(new DateTime($eventStart));
                $courseCalendar->setEnd(new DateTime($eventEnd));
                $this->courseCalendarRepository->persist($courseCalendar);
            }
            $this->calendarStaticEntryRepository->flush();
        }
    }


    /**
     * @return string
     */
    private function randomColor(): string
    {
        $rand = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f');

        return '#'.$rand[rand(0, 15)].$rand[rand(0, 15)].$rand[rand(0, 15)].$rand[rand(0, 15)].$rand[rand(0, 15)].$rand[rand(0, 15)];
    }
}
