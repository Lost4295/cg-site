<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use DateTime;
use DateTimeInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ApiResource(
    shortName: 'Utilisateurs',
    description: "Cette ressource est utilisée pour rassembler les informations utiles sur les élèves qui sont inscrits et usent du système centralisé pour les points OPEN.",
    normalizationContext: ['groups' => ['user:read']]
)]
#[Get(openapi: new Operation(
    responses: [
        '200' => new Response(description: 'L\'utilisateur demandé.'),
        '404' => new Response(description: 'Aucun utilisateur ne correspond à cet identifiant.'),
        '500' => new Response(description: 'Erreur interne du serveur.'),
    ],
    summary: 'Récupère un utilisateur.',
))]
#[GetCollection(openapi: new Operation(
    responses: [
        '200' => new Response(description: 'La liste des utilisateurs.'),
        '500' => new Response(description: 'Erreur interne du serveur.'),
    ],
    summary: 'Liste les utilisateurs.',
))]
#[Delete(
    openapi: new Operation(
        responses: [
            '204' => new Response(description: 'L\'utilisateur a été supprimé.'),
            '403' => new Response(description: 'Accès refusé : réservé aux super-administrateurs.'),
            '404' => new Response(description: 'Aucun utilisateur ne correspond à cet identifiant.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Supprime un utilisateur.',
    ),
    security: "is_granted('ROLE_SUPER_ADMIN')",
)]
#[Patch(openapi: new Operation(
    responses: [
        '200' => new Response(description: 'L\'utilisateur a été modifié.'),
        '400' => new Response(description: 'Requête invalide (corps mal formé ou types incorrects).'),
        '404' => new Response(description: 'Aucun utilisateur ne correspond à cet identifiant.'),
        '422' => new Response(description: 'Les données ne respectent pas les contraintes de validation.'),
        '500' => new Response(description: 'Erreur interne du serveur.'),
    ],
    summary: 'Modifie partiellement un utilisateur.',
))]
#[Post(openapi: new Operation(
    responses: [
        '201' => new Response(description: 'L\'utilisateur a été créé.'),
        '400' => new Response(description: 'Requête invalide (corps mal formé ou types incorrects).'),
        '422' => new Response(description: 'Les données ne respectent pas les contraintes de validation.'),
        '500' => new Response(description: 'Erreur interne du serveur.'),
    ],
    summary: 'Crée un utilisateur.',
))]
#[Put(openapi: new Operation(
    responses: [
        '200' => new Response(description: 'L\'utilisateur a été remplacé.'),
        '400' => new Response(description: 'Requête invalide (corps mal formé ou types incorrects).'),
        '404' => new Response(description: 'Aucun utilisateur ne correspond à cet identifiant.'),
        '422' => new Response(description: 'Les données ne respectent pas les contraintes de validation.'),
        '500' => new Response(description: 'Erreur interne du serveur.'),
    ],
    summary: 'Remplace un utilisateur.',
))]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements PasswordAuthenticatedUserInterface, UserInterface
{

    public const UNDEFINED = 0;
    public const NO_SET = 1;
    public const MISSING_DATA = 2;
    public const IS_OK = 3;

    public const ACCOUNT_VALID_VALUES = [
        self::UNDEFINED => 'Non défini',
        self::NO_SET => 'Jamais inscrit sur site',
        self::MISSING_DATA => 'Données manquantes',
        self::IS_OK => 'Compte validé',
    ];

    #[ORM\Id]
    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Identifiant unique de l\'utilisateur (identifiant Discord).', example: '123456789012345678')]
    #[ORM\Column(type: 'string', length: 32, unique: true, nullable: true)]
    private ?string $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['user:read'])]
    #[Assert\NotBlank(message: 'Pseudo cannot be blank.')]
    #[ApiProperty(description: 'Pseudonyme de l\'utilisateur, unique.', example: 'jdupont')]
    private ?string $pseudo;

    #[ORM\Column(type: 'string', length: 180, unique: true, nullable: true)]
    #[Assert\Email(message: 'The email {{ value }} is not a valid email.')]
    #[Assert\NotBlank(message: 'Email cannot be blank.')]
    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Adresse e-mail de l\'utilisateur, unique.', example: 'jean.dupont@example.com')]
    private ?string $email;

    #[Ignore]
    #[ORM\Column(type: 'json', nullable: true, options: ["default" => "[]"])]
    private array $roles = [];

    /** @var string[] */
    #[ORM\Column(type: Types::JSON, options: ['default' => '[]'])]
    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Identifiants des guildes (associations) gérées par l\'utilisateur.', example: ['1411453838774370397'])]
    private array $guildIds = [];

    #[Ignore]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avatar;

    #[Ignore]
    #[ORM\Column(nullable: true)]
    private ?DateTime $expiresIn = null;

    #[ORM\Column(options: ["default" => User::NO_SET])]
    #[Ignore]
    private ?int $accountValid = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Ignore]
    #[ApiProperty(description: 'Mot de passe haché de l\'utilisateur (donnée sensible).')]
    private ?string $password = null;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['default' => null])]
    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Numéro du groupe de la classe de l\'utilisateur. Si une personne est en 3SRC2, alors cette valeur sera 2.', example: 1)]
    private ?int $groupe = null;

    /**
     * @var Collection<int, CouchPoint>
     */
    #[ORM\OneToMany(targetEntity: CouchPoint::class, mappedBy: 'user', orphanRemoval: true)]
    #[Ignore]
    private Collection $couchPoints;

    /**
     * @var Collection<int, Point>
     */
    #[ORM\OneToMany(targetEntity: Point::class, mappedBy: 'user', orphanRemoval: true)]
    #[ApiProperty(description: 'Points attribués à l\'utilisateur.')]
    #[Groups(['user:read'])]
    private Collection $points;

    /**
     * @var Collection<int, Image>
     */
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'user', orphanRemoval: true)]
    #[Ignore]
    private Collection $images;

    /**
     * @var Collection<int, Participation>
     */
    #[Groups(['user:read'])]
    #[ORM\OneToMany(targetEntity:Participation::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[ApiProperty(description: 'Participations de l\'utilisateur à un évènement.')]
    private Collection $participations;

    /**
     * @var Collection<int, Question>
     */
    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'user')]
    #[Ignore]
    private Collection $questionsSubmitted;

    #[ORM\Column(length: 50)]
    #[Groups(['user:read'])]
    #[Assert\NotIdenticalTo('Nom à définir')]
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    #[ApiProperty(description: 'Nom de famille de l\'utilisateur.', example: 'Dupont')]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotIdenticalTo('Prénom à définir')]
    #[Groups(['user:read'])]
    #[Assert\NotBlank(message: 'Firstname cannot be blank.')]
    #[ApiProperty(description: 'Prénom de l\'utilisateur.', example: 'Jean')]
    private ?string $prenom = null;

    #[ORM\Column(length: 7)]
    #[Assert\NotIdenticalTo('Classe')]
    #[Assert\Regex(pattern: '/^(?:1(?:A|I|AJ)|2(?:A|I|MCSI|AJ)|[3-5](?:AL|IABD|MOC|IBC|IW|MCSI|RVJV|SI|SRC)J?)$/')]
    #[Assert\NotBlank(message: 'Class cannot be blank.')]
    #[ApiProperty(description: 'Classe de l\'utilisateur (7 caractères maximum). Ne doit pas contenir le groupe !', example: '3SI')]
    private ?string $classe = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Date d\'inscription de l\'utilisateur.', example: '2030-01-01T18:00:00+00:00')]
    private ?DateTime $date_inscr = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Indique si le profil de l\'utilisateur est visible publiquement.', example: false)]
    private ?bool $visibility = false;

    #[Groups(['user:read'])]
    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    #[ApiProperty(description: 'Indique si l\'utilisateur est administrateur (1) ou non (0).', example: 0)]
    #[Ignore]
    private ?int $is_admin = 0;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\OneToMany(targetEntity: Tag::class, mappedBy: 'user')]
    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Tags attribués à l\'utilisateur.')]
    private Collection $tags;


    /**
     * @var Collection<int, Event>
     */
    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'participants')]
    #[Ignore]
    private Collection $events;

    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Indique si l\'utilisateur est un étudiant de la promotion de janvier.', example: false)]
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $is_janvier = false;

    public function __construct()
    {
        $this->couchPoints = new ArrayCollection();
        $this->points = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->questionsSubmitted = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->participations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return "$this->nom $this->prenom, $this->classe$this->groupe ($this->pseudo)";
    }

    #[Groups(['user:read'])]
    #[ApiProperty(description: 'Nom complet (prénom et nom) de l\'utilisateur, calculé automatiquement.', example: 'Jean Dupont')]
    public function getFullName(): string
    {
        return "$this->prenom $this->nom";
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
    #[Ignore]
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = 'ROLE_USER';
        if ($this->is_admin) {
            $roles[] = 'ROLE_ADMIN';
            if ($this->is_admin === 2) {
                $roles[] = 'ROLE_SUPER_ADMIN';
            }
        }
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
    /** @return string[] */
    public function getGuildIds(): array
    {
        return array_map('strval', $this->guildIds);
    }

    /** @param string[] $guildIds */
    public function setGuildIds(array $guildIds): static
    {
        $this->guildIds = array_values(array_unique(array_map('strval', $guildIds)));

        return $this;
    }
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function hasGuild(string $guildId): bool
    {
        return in_array($guildId, $this->guildIds, true) && $this->hasRole('ROLE_GUILD_HANDLER');
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
        return "https://cdn.discordapp.com/avatars/$this->id/$this->avatar.webp";
    }

    public function setAvatar(?string $avatar): self
    {
        if ($avatar) {
            $this->avatar = $avatar;
        }

        return $this;
    }


    /**
     * @return Collection<int, CouchPoint>
     */
    public function getcouchPoints(): Collection
    {
        return $this->couchPoints;
    }

    public function addCouchPoint(CouchPoint $quizzPoint): static
    {
        if (!$this->couchPoints->contains($quizzPoint)) {
            $this->couchPoints->add($quizzPoint);
            $quizzPoint->setUser($this);
        }

        return $this;
    }

    public function removeCouchPoint(CouchPoint $quizzPoint): static
    {
        if ($this->couchPoints->removeElement($quizzPoint) && $quizzPoint->getUser() === $this) {
            // set the owning side to null (unless already changed)
            $quizzPoint->setUser(null);
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
        if ($this->points->removeElement($point) && $point->getUser() === $this) {
            // set the owning side to null (unless already changed)
            $point->setUser(null);
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
        if ($this->images->removeElement($image) && $image->getUser() === $this) {
            // set the owning side to null (unless already changed)
            $image->setUser(null);
        }

        return $this;
    }

    public function getParticipations(): ?Collection
    {
        return $this->participations;
    }

    public function setParticipations(Participation ...$participations): static
    {
        // set the owning side of the relation if necessary
        foreach ($participations as $participation) {
            if ($participation->getUser() !== $this) {
                $participation->setUser($this);
            }
        }
        $col = new ArrayCollection();

        $col->add($participations);
        $this->participations = $col;

        return $this;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setUser($this);
        }

        return $this;

    }
    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation) && $participation->getUser() === $this) {
            // set the owning side to null (unless already changed)
            $participation->setUser(null);
        }

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
        if ($this->questionsSubmitted->removeElement($questionsSubmitted) && $questionsSubmitted->getUser() === $this) {
            // set the owning side to null (unless already changed)
            $questionsSubmitted->setUser(null);
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

    #[Ignore]
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

        if ((int)$lastChar !== 0) {
            $this->groupe = (int)$lastChar;
            $this->classe = substr($value, 0, -1);
        } else {
            // Pas de groupe → groupe null
            $this->groupe = null;
            $this->classe = $value;
        }

        return $this;
    }

    #[Ignore]

    public function getDateInscr(): ?DateTime
    {
        return $this->date_inscr;
    }

    public function setDateInscr(DateTime $date_inscr): static
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

    #[Ignore]
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

    public function setPassword(string $hashPassword): User
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
        if ($this->tags->removeElement($tag) && $tag->getUser() === $this) {
            // set the owning side to null (unless already changed)
            $tag->setUser(null);
        }

        return $this;
    }

    #[Ignore]
    public function getIsAccountValid(): string
    {
        return $this::ACCOUNT_VALID_VALUES[$this->accountValid];
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

    #[Ignore]
    public function getRentree(): string
    {
        $octobre = ["1A1",

            "1A2",
            "1A3",
            "1A4",
            "1A5",
            "1A6",
            "1A7",
            "1I1",
            "1I2",
            "2A1",
            "2A2",
            "2A3",
            "2A4",
            "2A5",
            "2I1",
            "2I2",
            "2MCSI",
            "3AL1",
            "3AL2",
            "3IABD1",
            "3IABD2",
            "3MOC",
            "3IBC",
            "3IW1",
            "3IW2",
            "3MCSI1",
            "3MCSI2",
            "3RVJV",
            "3SI1",
            "3SI2",
            "3SI3",
            "3SI4",
            "3SI5",
            "3SRC1",
            "3SRC2",
            "3SRC3",
            "3SRC4",
            "3SRC5",
            "4AL1",
            "4AL2",
            "4IABD1",
            "4IABD2",
            "4MOC",
            "4IBC",
            "4IW1",
            "4IW2",
            "4IW3",
            "4MCSI1",
            "4MCSI2",
            "4MCSI3",
            "4MCSI4",
            "4RVJV",
            "4SI1",
            "4SI2",
            "4SI3",
            "4SI4",
            "4SI5",
            "4SRC1",
            "4SRC2",
            "4SRC3",
            "4SRC4",
            "4SRC5",
            "5MOC",
            "5IBC",
            "5IW1",
            "5IW2",
            "5AL1",
            "5AL2",
            "5MCSI1",
            "5MCSI2",
            "5MCSI3",
            "5IABD1",
            "5IABD2",
            "5RVJV",
            "5SI1",
            "5SI2",
            "5SI3",
            "5SRC1",
            "5SRC2",
            "5SRC3",
            "5SRC4",
        ];
        $janvier = [
            "1IJ",
            "1AJ1",
            "1AJ2",
            "2AJ1",
            "2AJ2",
            "3IABDJ",
            "3SIJ",
            "3SRCJ",
            "3IWJ",
            "3MCSIJ",
            "4IABDJ",
            "4SIJ",
            "4SRCJ",
            "4IWJ",
            "4MCSIJ",
            "5IABDJ",
            "5SIJ",
            "5SRCJ",
            "5IWJ",
            "5MCSIJ",
        ];
        if (in_array($this->getClasse(), $octobre, true)) {
            return "septembre";
        }

        if (in_array($this->getClasse(), $janvier, true)) {
            return "janvier";
        }
        return "inconnu";
    }

    #[Ignore]
    public function isJanvier(): ?bool
    {
        return $this->is_janvier;
    }

    public function setIsJanvier(bool $is_janvier): static
    {
        $this->is_janvier = $is_janvier;

        return $this;
    }
}

