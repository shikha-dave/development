<?php
/**
 * Created by PhpStorm.
 * User: shikhathakur
 * Date: 25.08.2021
 * Time: 10:50
 */

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class CourseCalendar
 * @ORM\Entity(repositoryClass="App\Repository\CourseCalendarRepository")
 * @ORM\Table(name="course_calendar")
 */
class CourseCalendar
{
    use IdTrait;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Serializer\Groups({"list", "detail"})
     */
    private ?string $professor = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text")
     *
     * @Serializer\Groups({"list", "detail"})
     */
    private ?string $courseName = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text")
     *
     * @Serializer\Groups({"list", "detail"})
     */
    private ?string $details = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text")
     *
     * @Serializer\Groups({"list", "detail"})
     */
    private ?string $name = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text")
     *
     * @Serializer\Groups({"list", "detail"})
     */
    private ?string $color = null;

    /**
     * @var Course|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Course")
     *
     * @Serializer\Groups({"list", "detail"})
     */
    private ?Course $course = null;

     /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime")
     *
     * @Serializer\Groups({"list", "detail"})
     * @Serializer\Type("DateTime<'Y-m-d H:i'>")
     */
    private ?\DateTime $start = null;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime")
     *
     * @Serializer\Groups({"list", "detail"})
     * @Serializer\Type("DateTime<'Y-m-d H:i'>")
     */
    private ?\DateTime $end = null;

    /**
     * @return string|null
     */
    public function getProfessor(): ?string
    {
        return $this->professor;
    }

    /**
     * @param string|null $professor
     *
     * @return CourseCalendar
     */
    public function setProfessor(?string $professor): CourseCalendar
    {
        $this->professor = $professor;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCourseName(): ?string
    {
        return $this->courseName;
    }

    /**
     * @param string|null $courseName
     *
     * @return CourseCalendar
     */
    public function setCourseName(?string $courseName): CourseCalendar
    {
        $this->courseName = $courseName;

        return $this;
    }

    /**
     * @return Course|null
     */
    public function getCourse(): ?Course
    {
        return $this->course;
    }

    /**
     * @param Course|null $course
     *
     * @return CourseCalendar
     */
    public function setCourse(?Course $course): CourseCalendar
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getStart(): ?\DateTime
    {
        return $this->start;
    }

    /**
     * @param \DateTime|null $start
     *
     * @return CourseCalendar
     */
    public function setStart(?\DateTime $start): CourseCalendar
    {
        $this->start = $start;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    /**
     * @param \DateTime|null $end
     *
     * @return CourseCalendar
     */
    public function setEnd(?\DateTime $end): CourseCalendar
    {
        $this->end = $end;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     *
     * @return CourseCalendar
     */
    public function setId(?string $id): CourseCalendar
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDetails(): ?string
    {
        return $this->details;
    }

    /**
     * @param string|null $details
     *
     * @return CourseCalendar
     */
    public function setDetails(?string $details): CourseCalendar
    {
        $this->details = $details;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @param string|null $color
     *
     * @return CourseCalendar
     */
    public function setColor(?string $color): CourseCalendar
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     *
     * @return CourseCalendar
     */
    public function setName(?string $name): CourseCalendar
    {
        $this->name = $name;

        return $this;
    }
}
