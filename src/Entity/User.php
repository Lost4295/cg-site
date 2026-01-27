<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements PasswordAuthenticatedUserInterface, UserInterface
{

    const UNDEFINED = 0;
    const NO_SET = 1;
    const MISSING_DATA = 2;
    const IS_OK = 3;

    const ACCOUNT_VALID_VALUES = [
        self::UNDEFINED => 'Non défini',
        self::NO_SET => 'Jamais inscrit sur site',
        self::MISSING_DATA => 'Données manquantes',
        self::IS_OK => 'Compte validé',
    ];

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32, unique: true, nullable: true)]
    private ?string $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private ?string $pseudo;

    #[ORM\Column(type: 'string', length: 180, unique: true, nullable: true)]
    private ?string $email;

    #[ORM\Column(type: 'json', nullable: true, options: ["default" => "[]"])]
    private array $roles = [];


    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avatar;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $accessToken = null;

    #[ORM\Column(nullable: true)]
    private ?DateTime $expiresIn = null;

    #[ORM\Column(options: ["default" => User::NO_SET])]
    private ?int $accountValid = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['default' => null])]
    private ?int $groupe = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?BlockedUser $blockedUser = null;

    /**
     * @var Collection<int, QuizzPoint>
     */
    #[ORM\OneToMany(targetEntity: QuizzPoint::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $quizzPoints;

    /**
     * @var Collection<int, Point>
     */
    #[ORM\OneToMany(targetEntity: Point::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $points;

    /**
     * @var Collection<int, Image>
     */
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $images;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Participation $participations = null;

    /**
     * @var Collection<int, Question>
     */
    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'user')]
    private Collection $questionsSubmitted;

    #[ORM\Column(length: 50)]
    #[Assert\NotIdenticalTo('Nom à définir')]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotIdenticalTo('Prénom à définir')]
    private ?string $prenom = null;

    #[ORM\Column(length: 7)]
    #[Assert\NotIdenticalTo('Classe')]
    private ?string $classe = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private ?int $warns = 0;

    #[ORM\Column]
    private ?\DateTime $date_inscr = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $visibility = false;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private ?int $is_admin = 0;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\OneToMany(targetEntity: Tag::class, mappedBy: 'user')]
    private Collection $tags;

    #[ORM\Column(nullable: true)]
    private ?bool $active = null;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'participants')]
    private Collection $events;

    public function __construct()
    {
        $this->quizzPoints = new ArrayCollection();
        $this->points = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->questionsSubmitted = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function __toString(): string
    {
        return "$this->nom $this->prenom, $this->classe$this->groupe ($this->pseudo)";
    }

    public function getFullName(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return "{$this->pseudo}-{$this->email}";
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
// If you store any temporary, sensitive data on the user, clear it here
// $this->plainPassword = null;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): self
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return "https://cdn.discordapp.com/avatars/{$this->id}/{$this->avatar}.webp";
    }

    public function setAvatar(string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getBlockedUser(): ?BlockedUser
    {
        return $this->blockedUser;
    }

    public function isBlocked(): bool
    {
        return isset($this->blockedUser);
    }

    public function setBlockedUser(BlockedUser $blockedUser): static
    {
        // set the owning side of the relation if necessary
        if ($blockedUser->getUser() !== $this) {
            $blockedUser->setUser($this);
        }

        $this->blockedUser = $blockedUser;

        return $this;
    }

    /**
     * @return Collection<int, QuizzPoint>
     */
    public function getQuizzPoints(): Collection
    {
        return $this->quizzPoints;
    }

    public function addQuizzPoint(QuizzPoint $quizzPoint): static
    {
        if (!$this->quizzPoints->contains($quizzPoint)) {
            $this->quizzPoints->add($quizzPoint);
            $quizzPoint->setUser($this);
        }

        return $this;
    }

    public function removeQuizzPoint(QuizzPoint $quizzPoint): static
    {
        if ($this->quizzPoints->removeElement($quizzPoint)) {
            // set the owning side to null (unless already changed)
            if ($quizzPoint->getUser() === $this) {
                $quizzPoint->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Point>
     */
    public function getPoints(): Collection
    {
        return $this->points;
    }

    public function addPoint(Point $point): static
    {
        if (!$this->points->contains($point)) {
            $this->points->add($point);
            $point->setUser($this);
        }

        return $this;
    }

    public function removePoint(Point $point): static
    {
        if ($this->points->removeElement($point)) {
            // set the owning side to null (unless already changed)
            if ($point->getUser() === $this) {
                $point->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setUser($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getUser() === $this) {
                $image->setUser(null);
            }
        }

        return $this;
    }

    public function getParticipations(): ?Participation
    {
        return $this->participations;
    }

    public function setParticipations(Participation $participations): static
    {
        // set the owning side of the relation if necessary
        if ($participations->getUser() !== $this) {
            $participations->setUser($this);
        }

        $this->participations = $participations;

        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestionsSubmitted(): Collection
    {
        return $this->questionsSubmitted;
    }

    public function addQuestionsSubmitted(Question $questionsSubmitted): static
    {
        if (!$this->questionsSubmitted->contains($questionsSubmitted)) {
            $this->questionsSubmitted->add($questionsSubmitted);
            $questionsSubmitted->setUser($this);
        }

        return $this;
    }

    public function removeQuestionsSubmitted(Question $questionsSubmitted): static
    {
        if ($this->questionsSubmitted->removeElement($questionsSubmitted)) {
            // set the owning side to null (unless already changed)
            if ($questionsSubmitted->getUser() === $this) {
                $questionsSubmitted->setUser(null);
            }
        }

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getClasse(): ?string
    {
        if (!$this->classe) {
            return null;
        }

        if (!$this->groupe) {
            return $this->classe;
        }
        return $this->classe . $this->groupe;
    }

    public function getOnlyClasse(): ?string
    {
        if (!$this->classe) {
            return null;
        }
        return $this->classe;
    }

    public function setClasse(?string $value): static
    {
        if (!$value) {
            return $this;
        }

        $lastChar = substr($value, -1);

        if (intval($lastChar) !== 0) {
            $this->groupe = intval($lastChar);
            $this->classe = substr($value, 0, -1);
        } else {
            // Pas de groupe → groupe null
            $this->groupe = null;
            $this->classe = $value;
        }

        return $this;
    }

    public function getWarns(): ?int
    {
        return $this->warns;
    }

    public function setWarns(int $warns): static
    {
        $this->warns = $warns;

        return $this;
    }

    public function getDateInscr(): ?\DateTime
    {
        return $this->date_inscr;
    }

    public function setDateInscr(\DateTime $date_inscr): static
    {
        $this->date_inscr = $date_inscr;

        return $this;
    }

    public function isVisibility(): ?bool
    {
        return $this->visibility;
    }

    public function setVisibility(bool $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getIsAdmin(): ?int
    {
        return $this->is_admin;
    }

    public function setIsAdmin(int $is_admin): static
    {
        $this->is_admin = $is_admin;

        return $this;
    }

    public function getAccountValid(): ?int
    {
        return $this->accountValid;
    }

    public function setAccountValid(?int $accountValid): void
    {
        $this->accountValid = $accountValid;
    }

    public function getExpiresIn(): ?DateTimeInterface
    {
        return $this->expiresIn;
    }

    public function setExpiresIn(?DateTimeInterface $expiresIn): void
    {
        $this->expiresIn = $expiresIn;
    }

    public function setPassword(string $hashPassword)
    {
        $this->password = $hashPassword;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->setUser($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        if ($this->tags->removeElement($tag)) {
            // set the owning side to null (unless already changed)
            if ($tag->getUser() === $this) {
                $tag->setUser(null);
            }
        }

        return $this;
    }

    public function getIsAccountValid()
    {
        return $this::ACCOUNT_VALID_VALUES[$this->accountValid];
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->addParticipant($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            $event->removeParticipant($this);
        }

        return $this;
    }

}
