<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use App\Repository\DateRepository;
use ArrayObject;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: DateRepository::class)]
#[ORM\Table(name: 'dates')]
#[ApiResource(
    shortName: 'Dates',
    description: "Cette ressource est utilisée pour gérer les dates d'événements ou de sessions d'association.
    Elle permet de créer, modifier et supprimer des dates.
    Elle peut s'utiliser en conjonction avec la ressource Participation pour gérer les participations/présences des utilisateurs aux dates créées.
    ",
    normalizationContext: ['groups' => ['user:read']]
)]
#[Get(
    openapi: new Operation(
        responses: [
            '200' => new OpenApiResponse(
                description: 'La date demandée.',
                content: new ArrayObject([
                    'application/json' => new MediaType(
                        schema: new ArrayObject([
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'required' => ['id', 'title', 'description', 'date', 'distanciel', 'guild_id'],
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'title' => ['type' => 'string'],
                                    'description' => ['type' => 'string'],
                                    'date' => ['type' => 'string', 'format' => 'date-time'],
                                    'distanciel' => ['type' => 'boolean'],
                                    'guild_id' => ['type' => 'string'],
                                ],
                            ],
                        ]),
                        example: [
                            [
                                'id' => 1,
                                'title' => 'Titre de la date',
                                'description' => 'Description de la date',
                                'date' => '2026-10-26T10:55:01.167Z',
                                'distanciel' => true,
                                'guild_id' => '1A1',
                            ],
                        ],
                    ),
                ]),
                summary: 'Les informations de la date demandée.',
            ),

            '404' => new Response(description: 'Aucune date ne correspond à cet identifiant.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: "Récupère une date par son identifiant.",
    ),
    description: "Récupère les informations d'une date spécifique par son ID.",
)]
#[GetCollection(
    openapi: new Operation(
        responses: [
            '200' => new OpenApiResponse(
                description: 'Liste des dates disponibles.',
                content: new ArrayObject([
                    'application/json' => new MediaType(
                        schema: new ArrayObject([
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'required' => [
                                    'id',
                                    'title',
                                    'description',
                                    'date',
                                    'distanciel',
                                    'guild_id',
                                ],
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'title' => ['type' => 'string'],
                                    'description' => ['type' => 'string'],
                                    'date' => [
                                        'type' => 'string',
                                        'format' => 'date-time',
                                    ],
                                    'distanciel' => ['type' => 'boolean'],
                                    'guild_id' => ['type' => 'string'],
                                ],
                            ],
                        ]),
                        example: [
                            [
                                'id' => 1,
                                'title' => 'Soirée jeux de société',
                                'description' => 'Une soirée conviviale autour de jeux.',
                                'date' => '2030-01-01T18:00:00+00:00',
                                'distanciel' => false,
                                'guild_id' => '1411453838774370397',
                            ],
                            [
                                'id' => 2,
                                'title' => 'Réunion en ligne',
                                'description' => 'Réunion mensuelle de l’association.',
                                'date' => '2030-01-15T19:00:00+00:00',
                                'distanciel' => true,
                                'guild_id' => '1411453838774370397',
                            ],
                        ],
                    ),
                ]),
                summary: 'Retourne la liste des dates.',
            ),
            '500' => new Response(
                description: 'Erreur interne du serveur.',
            ),
        ],
        summary: 'Liste les dates.',
    ),
    description: "Récupère la liste de toutes les dates disponibles. Il est possible de filtrer les dates par date en utilisant le paramètre 'date' dans la requête. Le format attendu pour le filtre de date est 'YYYY-MM-DD'.",
    parameters: [
        'date' => new QueryParameter(
            filter: new DateFilter(),
            property: 'date',
        ),
    ]
)]
#[Delete(
    openapi: new Operation(
        responses: [
            '204' => new Response(description: 'La date a été supprimée.'),
            '403' => new Response(description: 'Accès refusé : réservé aux super-administrateurs.'),
            '404' => new Response(description: 'Aucune date ne correspond à cet identifiant.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Supprime une date.',
    ),
    description: 'Supprime une date spécifique par son ID. Cette opération est réservée aux super-administrateurs.',
    security: "is_granted('ROLE_SUPER_ADMIN')",
)]
#[Patch(
    openapi: new Operation(
        responses: [
            '200' => new Response(description: 'La date a été modifiée.'),
            '400' => new Response(description: 'Requête invalide (corps mal formé ou types incorrects).'),
            '404' => new Response(description: 'Aucune date ne correspond à cet identifiant.'),
            '422' => new Response(description: 'Les données ne respectent pas les contraintes de validation.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Modifie partiellement une date.',
    ),
    description: 'Modifie partiellement une date spécifique par son ID. Cette opération est réservée aux associations que vous gérez.',
    securityPostDenormalize: "is_granted('ROLE_SUPER_ADMIN') or user.hasGuild(object.getGuildId())",
)]
#[Post(
    openapi: new Operation(
        responses: [
            '201' => new Response(description: 'La date a été créée.'),
            '400' => new Response(description: 'Requête invalide (corps mal formé ou types incorrects).'),
            '422' => new Response(description: 'Les données ne respectent pas les contraintes de validation.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Crée une date.',
    ),
    description: 'Crée une nouvelle date. Cette opération est réservée aux associations que vous gérez.',
    securityPostDenormalize: "is_granted('ROLE_SUPER_ADMIN') or user.hasGuild(object.getGuildId())",
)]
#[Put(
    openapi: new Operation(
        responses: [
            '200' => new Response(description: 'La date a été remplacée.'),
            '400' => new Response(description: 'Requête invalide (corps mal formé ou types incorrects).'),
            '404' => new Response(description: 'Aucune date ne correspond à cet identifiant.'),
            '422' => new Response(description: 'Les données ne respectent pas les contraintes de validation.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Remplace une date.',
    ),
    description: 'Modifie une date spécifique par son ID. Cette opération est réservée aux associations que vous gérez.',
    securityPostDenormalize: "is_granted('ROLE_SUPER_ADMIN') or user.hasGuild(object.getGuildId())",
)]
class Date
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['user:read'])]
    #[ApiProperty(
        description: "Cette propriété représente l'identifiant unique de la date dans la base de données. Elle est générée automatiquement lors de la création d'une nouvelle date et ne peut pas être modifiée. L'ID est utilisé pour identifier de manière unique chaque date et est souvent utilisé dans les URL pour accéder aux détails d'une date spécifique.",
        readable: true,
        writable: false
    )]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Title cannot be blank.')]
    #[Groups(['user:read'])]
    #[ApiProperty(
        description: "Titre de l'événement ou de la session (255 caractères maximum).",
        example: 'Soirée jeux de société',
    )]

    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['user:read'])]
    #[Assert\NotBlank(message: 'Description cannot be blank.')]
    #[ApiProperty(
        description: "Description détaillée de l'événement : programme, lieu, informations pratiques.",
        example: 'Venez nombreux à notre soirée jeux de société ! Nous vous proposons une sélection de jeux pour tous les goûts, des classiques aux nouveautés. Des boissons et des snacks seront disponibles sur place. N\'hésitez pas à apporter vos propres jeux et à inviter vos amis !',
    )]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    #[Assert\NotBlank(message: 'Date cannot be blank.')]
    #[ApiProperty(
        description: "Date et heure de l'événement, au format ISO 8601.",
        example: '2030-01-01T18:00:00+00:00',
    )]
    private ?DateTime $date = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    #[Assert\NotNull(message: 'You must specify if the event is online or not.')]
    #[ApiProperty(
        description: "Indique si l'événement se déroule à distance (true) ou en présentiel (false).",
        example: false,
    )]
    private ?bool $distanciel = null;

    #[ORM\OneToOne(mappedBy: 'date', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    #[ApiProperty(
        description: "Participation associée à cette date : permet de suivre la présence des utilisateurs à l'événement.",
    )]
    private ?Participation $participations = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['user:read'])]
    #[ApiProperty(
        description: "Identifiant Discord de la guilde (association) qui organise l'événement. Vous devez gérer cette guilde pour créer ou modifier la date.",
        example: '1411453838774370397',
    )]
    #[Assert\NotBlank(message: 'Guild ID cannot be blank.')]
    #[Assert\Length(max: 50)]
    private string $guild_id;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function isDistanciel(): ?bool
    {
        return $this->distanciel;
    }

    public function setDistanciel(bool $distanciel): static
    {
        $this->distanciel = $distanciel;

        return $this;
    }

    public function getParticipations(): ?Participation
    {
        return $this->participations;
    }

    public function setParticipations(Participation $participations): static
    {
        // set the owning side of the relation if necessary
        if ($participations->getDate() !== $this) {
            $participations->setDate($this);
        }

        $this->participations = $participations;

        return $this;
    }
    #[Ignore]
    public function getGuildId(): string
    {
        return $this->guild_id;
    }

    public function setGuildId(string $guild_id): void
    {
        $this->guild_id = $guild_id;
    }
}
