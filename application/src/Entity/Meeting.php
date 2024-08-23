<?php

namespace App\Entity;

use App\Repository\MeetingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use stdClass;

/**
 * @ORM\Entity(repositoryClass=MeetingRepository::class)
 */
class Meeting
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true, nullable=false)
     */
    private $meetingID;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $attendeePW;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $moderatorPW;

    /**
     * @ORM\Column(type="integer")
     */
    private $voiceBridge = 7000;

    /**
     * @ORM\Column(type="integer")
     */
    private $dialNumber = 0000;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $meetingName;

    /**
     * @ORM\Column(type="boolean")
     */
    private $disableCam = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $disableMic = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $disablePrivateChat = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $disablePublicChat = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $disableNotes= false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $lockedLayout = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hideUserList = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $lockOnJoin = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $lockOnJoinConfigurable = false;

    /**
     * @ORM\Column(type="json")
     */
    private $metadata = [
        'bbb-context' => '',
        'bbb-context-id' => '',
        'bbb-context-label' => '',
        'bbb-context-name' => '',
        'bbb-origin' => 'Moodle',
        'bbb-origin-server-common-name' => 'HOSTNAME',
        'bbb-origin-server-name' => 'BBB Moodle',
        'bbb-origin-tag' => "moodle-mod_bigbluebuttonbn (PLUGINVERSION)",
        'bbb-origin-version' => "RELEASE",
    ];

    /**
     * @ORM\Column(type="datetime")
     */
    private $createTime;

    /**
     * @ORM\Column(type="datetime")
     */
    private $startTime;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $endTime;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hasUserJoined = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hasBeenForciblyEnded = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $recording = true;

    /**
     * @ORM\Column(type="integer")
     */
    private $maxUsers = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $running = false;

    /**
     * @ORM\OneToMany(targetEntity=Attendee::class, mappedBy="meeting", orphanRemoval=true, fetch="EAGER")
     */
    private $attendees;

    /**
     * @ORM\OneToMany(targetEntity=Event::class, mappedBy="meeting", orphanRemoval=true, fetch="EAGER")
     */
    private $events;

    /**
     * @ORM\OneToMany(targetEntity=Recording::class, mappedBy="meeting", orphanRemoval=true)
     */
    private $recordings;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $serverID;


    /**
     * @ORM\Column(type="boolean")
     */
    private $isBreakout = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $freeJoin = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $breakoutRoomsEnabled = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $breakoutRoomsPrivateChatEnabled = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $breakoutRoomsRecord = true;

    /**
     * @ORM\Column(type="integer")
     */
    private $sequence = 0;

    /**
     * @ORM\ManyToOne(targetEntity=Meeting::class, inversedBy="childMeetings")
     * @ORM\JoinColumn(nullable=true)
     */
    private $parentMeeting = null;

    /**
     * @ORM\OneToMany(targetEntity=Meeting::class, mappedBy="parentMeeting", orphanRemoval=true, fetch="EAGER")
     */
    private $childMeetings;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $analyticsCallbackURL = null;

    public function __construct()
    {
        $this->createTime = new \DateTime();
        $this->startTime = $this->createTime;
        $this->attendees = new ArrayCollection();
        $this->recordings = new ArrayCollection();
    }

    public function getMeetingInfo(): stdClass {
        // Filter out attendee's object as it does not translate well in XML as such.
        $attendees = $this->getAttendees()->toArray();
        if ($attendees) {
            foreach ($attendees as $key => $val) {
                $attendee = (array) $val;
                $attendee =
                    array_intersect_key(['userID', 'fullName', 'role', 'isPresenter', 'isListeningOnly', 'hasVideo', 'clientType'],
                        $attendee);
                $attendees[$key] = (object) $attendee;
            }
        }
        $meetingInfo = (object) [
            'meetingName' => $this->meetingName,
            'meetingID' => $this->meetingID,
            //'internalMeetingID' => $this->internalMeetingID,
            //'parentMeetingID' => $this->parentMeetingID,
            'createTime' => $this->createTime->format('U'),
            'createDate' => $this->createTime->format('D M d H:i:s e Y'),
            'voiceBridge' => sprintf("%04d", $this->voiceBridge),
            'dialNumber' => sprintf("%04d", $this->dialNumber),
            'attendeePW' => $this->attendeePW,
            'moderatorPW' => $this->moderatorPW,
            'running' => $this->stringifyBool($this->running),
            'hasUserJoined' => $this->stringifyBool($this->hasUserJoined),
            'recording' => $this->stringifyBool($this->recording),
            'hasBeenForciblyEnded' => $this->stringifyBool($this->hasBeenForciblyEnded),
            'startTime' => $this->startTime->format('U'),
            'endTime' => $this->endTime ? $this->endTime->format('U') : 0,
            'participantCount' => $this->getParticipantCount(),
            'listenerCount' => $this->getListenerCount(),
            'videoCount' => $this->getVideoCount(),
            'moderatorCount' => $this->getModeratorCount(),
            'attendees' => $attendees,
            'metadata' => $this->metadata,
            'duration' => (new \DateTime())->diff($this->createTime)->s,
            'isBreakout' => $this->isBreakout()
        ];
        if ($this->hasSubMeetings()) {
            $breakoutRooms = [];
            foreach($this->getChildMeetings() as $childMeeting) {
                $breakoutRooms[] = $childMeeting->getMeetingID();
            }
            $meetingInfo->breakoutRooms = (object) [
                'forcexmlarraytype' => 'breakout',
                'array' => $breakoutRooms,
            ];
        }
        if ($this->isBreakout()) {
            $meetingInfo->breakout = (object) [
                'parentMeetingID' =>$this->getParentMeeting()->getMeetingID(),
                'sequence'=> $this->getBreakoutSequence(),
                'freeJoin' => $this->isFreeJoin()
            ];
        }
        return $meetingInfo;
    }

    public function getMeetingSummary(): stdClass {
        return (object) [
            'meetingID' => $this->meetingID,
            'attendeePW' => $this->attendeePW,
            'moderatorPW' => $this->moderatorPW,
            'createDate' => $this->createTime->format('D M d H:i:s e Y'),
            'createTime' => $this->createTime->format('U'),
            'dialNumber' => sprintf("%04d", $this->dialNumber),
            'voiceBridge' => sprintf("%04d", $this->voiceBridge),
            'hasBeenForciblyEnded' => $this->stringifyBool($this->hasBeenForciblyEnded),
            'hasUserJoined' => $this->stringifyBool($this->hasUserJoined),
        ];
    }

    protected function stringifyBool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMeetingID(): ?string
    {
        return $this->meetingID;
    }

    public function setMeetingID(string $meetingID): self
    {
        $this->meetingID = $meetingID;

        return $this;
    }

    public function getAttendeePW(): ?string
    {
        return $this->attendeePW;
    }

    public function setAttendeePW(string $attendeePW): self
    {
        $this->attendeePW = $attendeePW;

        return $this;
    }

    public function checkAttendeePW(string $attendeePW): bool
    {
        return $attendeePW === $this->attendeePW;
    }

    public function getModeratorPW(): ?string
    {
        return $this->moderatorPW;
    }

    public function setModeratorPW(string $moderatorPW): self
    {
        $this->moderatorPW = $moderatorPW;

        return $this;
    }

    public function checkModeratorPW(string $moderatorPW): bool
    {
        return $moderatorPW === $this->moderatorPW;
    }

    public function getVoiceBridge(): ?int
    {
        return $this->voiceBridge;
    }

    public function setVoiceBridge(int $voiceBridge): self
    {
        $this->voiceBridge = $voiceBridge;

        return $this;
    }

    public function getDialNumber(): ?int
    {
        return $this->dialNumber;
    }

    public function setDialNumber(int $dialNumber): self
    {
        $this->dialNumber = $dialNumber;

        return $this;
    }

    public function getMeetingName(): ?string
    {
        return $this->meetingName;
    }

    public function setMeetingName(string $meetingName): self
    {
        $this->meetingName = $meetingName;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTimeInterface $createTime): self
    {
        $this->createTime = $createTime;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getHasUserJoined(): ?bool
    {
        return $this->hasUserJoined;
    }

    public function setHasUserJoined(bool $hasUserJoined): self
    {
        $this->hasUserJoined = $hasUserJoined;

        return $this;
    }

    public function getHasBeenForciblyEnded(): ?bool
    {
        return $this->hasBeenForciblyEnded;
    }

    public function setHasBeenForciblyEnded(bool $hasBeenForciblyEnded): self
    {
        $this->hasBeenForciblyEnded = $hasBeenForciblyEnded;

        return $this;
    }

    public function getRecording(): ?bool
    {
        return $this->recording;
    }

    public function setRecording(bool $recording): self
    {
        $this->recording = $recording;

        return $this;
    }

    public function getMaxUsers(): ?int
    {
        return $this->maxUsers;
    }

    public function setMaxUsers(int $maxUsers): self
    {
        $this->maxUsers = $maxUsers;

        return $this;
    }

    public function getRunning(): ?bool
    {
        return $this->running;
    }

    public function setRunning(bool $running): self
    {
        $this->running = $running;

        return $this;
    }

    /**
     * @return Collection|Attendee[]
     */
    public function getAttendees(): Collection
    {
        return $this->attendees;
    }


    /**
     * @return Collection|Event[]
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addAttendee(Attendee $attendee): self
    {
        if (!$this->attendees->contains($attendee)) {
            $this->attendees[] = $attendee;
            $attendee->setMeeting($this);
        }

        return $this;
    }

    public function addEvent(Event $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->setMeeting($this);
        }

        return $this;
    }

    public function removeAttendee(Attendee $attendee): self
    {
        if ($this->attendees->removeElement($attendee)) {
            // set the owning side to null (unless already changed)
            if ($attendee->getMeeting() === $this) {
                $attendee->setMeeting(null);
            }
        }

        return $this;
    }

    public function getParticipantCount(): int
    {
        return count($this->getAttendees());
    }

    public function getModeratorCount(): int
    {
        return count($this->getAttendees()->filter(function($attendee): bool
        {
            return $attendee->isModerator();
        }));
    }

    public function getVideoCount(): int
    {
        return count($this->getAttendees()->filter(function($attendee): bool
        {
            return $attendee->hasVideo();
        }));
    }

    public function getListenerCount(): int
    {
        return count($this->getAttendees()->filter(function($attendee): bool
        {
            return $attendee->hasJoinedVoice();
        }));
    }

    /**
     * @return Collection|Recording[]
     */
    public function getRecordings(): Collection
    {
        return $this->recordings;
    }

    public function addRecording(Recording $recording): self
    {
        if (!$this->recordings->contains($recording)) {
            $this->recordings[] = $recording;
        }
        $recording->setMeeting($this);
        return $this;
    }

    public function removeRecording(Recording $recording): self
    {
        if ($this->recordings->removeElement($recording)) {
            // set the owning side to null (unless already changed)
            if ($recording->getMeeting() === $this) {
                $recording->setMeeting(null);
            }
        }

        return $this;
    }

    public function getServerID(): ?string
    {
        return $this->serverID;
    }

    public function setServerID(string $serverID): self
    {
        $this->serverID = $serverID;
        return $this;
    }

    public function setLockSetting(string $lockName, bool $value) {
        if (property_exists($this, $lockName)) {
            $this->$lockName = $value;
        }
    }

    public function getLockSetting(string $lockName): bool {
        if (property_exists($this, $lockName)) {
            // If lockOnJoin not set to true, this is false for every lock.
            return $this->lockOnJoin && $this->$lockName;
        }
        return false;
    }

    public function isBreakout(): ?bool
    {
        return $this->isBreakout;
    }

    public function setIsBreakout(bool $val): self
    {
        $this->isBreakout = $val;
        return $this;
    }

    public function isFreeJoin(): ?bool
    {
        return $this->freeJoin;
    }

    public function setIsFreeJoin(bool $val): self
    {
        $this->freeJoin = $val;
        return $this;
    }

    public function isBreakoutRoomsEnabled(): ?bool
    {
        return $this->breakoutRoomsEnabled;
    }

    public function setIsBreakoutRoomsEnabled(bool $val): self
    {
        $this->breakoutRoomsEnabled = $val;
        return $this;
    }

    public function isBreakoutRoomsPrivateChatEnabled(): ?bool
    {
        return $this->breakoutRoomsPrivateChatEnabled;
    }

    public function setIsBreakoutRoomsPrivateChatEnabled(bool $val): self
    {
        $this->breakoutRoomsPrivateChatEnabled = $val;
        return $this;
    }

    public function isBreakoutRoomsRecord(): ?bool
    {
        return $this->breakoutRoomsRecord;
    }

    public function setIsBreakoutRoomsRecord(bool $val): self
    {
        $this->breakoutRoomsRecord = $val;
        return $this;
    }

    public function getBreakoutSequence(): ?int
    {
        return $this->sequence;
    }

    public function setBreakoutSequence(int $val): self
    {
        $this->sequence = $val;
        return $this;
    }

    public function addChildMeeting(Meeting $childMeeting): self
    {
        if (!$this->childMeetings->contains($childMeeting)) {
            $this->childMeetings[] = $childMeeting;
        }
        $childMeeting->parentMeeting  = $this;
        return $this;
    }

    public function removeChildMeeting(Meeting $childMeeting): self
    {
        if ($this->childMeetings->removeElement($childMeeting)) {
            // set the owning side to null (unless already changed)
            if ($childMeeting->getParentMeeting() === $this) {
                $childMeeting->setParentMeeting(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection|Meeting[]
     */
    public function getChildMeetings(): Collection
    {
        return $this->childMeetings;
    }

    public function setParentMeeting(?Meeting $parentMeeting): self {
        if ($parentMeeting) {
            $parentMeeting->addChildMeeting($this);
        } else {
            $this->parentMeeting = null;
        }
        return $this;
    }
    public function getParentMeeting(): ?Meeting {
        return $this->parentMeeting ;
    }

    public function hasSubMeetings(): ?bool
    {
        return !empty($this->childMeetings);
    }

    public function setAnalyticsCallbackURL(?string $url) {
        $this->analyticsCallbackURL = $url;
    }

    public function getAnalyticsCallbackURL(): string {
        return $this->analyticsCallbackURL;
    }

}
