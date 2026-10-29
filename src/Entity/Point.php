<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use App\Controller\ApiController;
use App\Controller\PointBatchController;
use App\Repository\PointRepository;
use ArrayObject;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: PointRepository::class)]
#[ORM\Table(name: 'points')]
#[ApiResource(shortName: 'Points',
    description: "Cette ressource permet de gérer les points des membres. Vous êtes sûrement venus ici pour ça."
)]
#[GetCollection(
    uriTemplate: '/points/summary',
    controller: ApiController::class . '::pointsSummary',
    openapi: new OpenApiOperation(summary: 'Total des points OPEN de l\'utilisateur (avec nom de l\'association)'),
    read: false,
    deserialize: false,
    validate: false,
    serialize: false,
    name: 'points_summary',
)]
#[Post(
    uriTemplate: '/points/batch',
    inputFormats: ['json' => ['application/json']],
    outputFormats: ['json' => ['application/json']],
    controller: PointBatchController::class . '::batch',
    openapi: new OpenApiOperation(
        responses: [
            '201' => new OpenApiResponse(
                description: 'Points créés',
                content: new ArrayObject(['application/json' => new MediaType(schema: new ArrayObject(self::BATCH_RESPONSE_SCHEMA))]),
            ),
            '422' => new OpenApiResponse(description: 'Rien n\'a été créé : liste des erreurs'),
        ],
        summary: 'Crée des entrées de point pour plusieurs utilisateurs en même temps (tout ou rien, max 500 lignes)',
        requestBody: new RequestBody(
            content: new ArrayObject([
                'application/json' => new MediaType(schema: new ArrayObject([
                    'type' => 'object',
                    'required' => ['points', 'members'],
                    'properties' => [
                        'points' => ['type' => 'number', 'example' => 1],
                        'reason' => ['type' => 'string', 'example' => 'lorem ipsum'],
                        'members' => ['type' => 'array', 'items' => ['type' => 'string', 'format' => 'email'], 'example' => ['mail1@example.com', 'mail2@example.com']],
                        'guild_id' => ['type' => 'string', 'description' => 'Requis seulement si vous appartenez à plusieurs guildes.'],
                    ],
                ])),
            ]),
            required: true,
        ),
    ),
    read: false,
    deserialize: false,
    validate: false,
    serialize: false,
    name: 'points_batch',
)]
#[Post(
    uriTemplate: '/points/batch/csv',
    inputFormats: ['multipart' => ['multipart/form-data']],
    outputFormats: ['json' => ['application/json']],
    controller: PointBatchController::class . '::batchCsv',
    openapi: new OpenApiOperation(
        responses: [
            '201' => new OpenApiResponse(
                description: 'Points créés',
                content: new ArrayObject(['application/json' => new MediaType(schema: new ArrayObject(self::BATCH_RESPONSE_SCHEMA))]),
            ),
            '422' => new OpenApiResponse(description: 'Rien n\'a été créé : liste des erreurs en lignes'),
        ],
        summary: 'Crée des points à partir d\'un fichier CSV avec les colonnes mail,point,raison (tout ou rien, max 500 lignes)',
        requestBody: new RequestBody(
            content: new ArrayObject([
                'multipart/form-data' => new MediaType(schema: new ArrayObject([
                    'type' => 'object',
                    'required' => ['file'],
                    'properties' => [
                        'file' => ['type' => 'string', 'format' => 'binary', 'description' => 'CSV avec l\'entête mail,point,raison (séparateur , or ;)'],
                        'guild_id' => ['type' => 'string', 'description' => 'Requis seulement si vous appartenez à plusieurs guildes.'],
                    ],
                ])),
            ]),
            required: true,
        ),
    ),
    read: false,
    deserialize: false,
    validate: false,
    serialize: false,
    name: 'points_batch_csv',
)]
#[GetCollection(
    uriTemplate: '/points/detailed/{guildId}',
    outputFormats: ['json' => ['application/json']],
    uriVariables: ['guildId' => new Link(
        schema: ['type' => 'string'],
        description: 'L\'identifiant de l\'association dont vous voulez avoir les points.',
        required: true,
    )],
    controller: ApiController::class . '::pointsByGuild',
    openapi: new OpenApiOperation(
        responses: [
            '200' => new OpenApiResponse(
                description: 'Points détaillés de l\'association',
                content: new ArrayObject([
                    'application/json' => new MediaType(schema: new ArrayObject([
                        'type' => 'object',
                        'properties' => [
                            'guildId' => ['type' => 'string'],
                            'detailed' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'id' => ['type' => 'integer'],
                                        'fullName' => ['type' => 'string'],
                                        'reason' => ['type' => 'string', 'nullable' => true],
                                        'date' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                                        'points' => ['type' => 'number'],
                                    ],
                                ],
                            ],
                        ],
                    ])),
                ]),
            ),
        ],
        summary: 'Donne tous les points renseignés dans l\'association, avec les détails',
    ),
    read: false,
    deserialize: false,
    validate: false,
    serialize: false,
    name: 'points_by_guild',
    parameters: [
        'classe' => new QueryParameter(
            key: 'classe',
            schema: ['type' => 'string'],
            description: 'Optionnel: restreindre les résultats aux points donnés aux étudiants de cette classe (par exemple, 3SI1).',
            required: false,
        ),
        'date' => new QueryParameter(
            filter: new DateFilter(),
            property: 'date',
        ),
        'slug' => new QueryParameter(
            key: 'slug',
            schema: ['type' => 'string'],
            description: 'Optionnel: quand il est fourni, le paramètre guildId est ignoré, au profit de l\'utilisation du slug.',
            required: false,
        ),
    ],
)]
#[GetCollection(
    uriTemplate: '/points/users/{guildId}',
    outputFormats: ['json' => ['application/json']],
    uriVariables: ['guildId' => new Link(
        schema: ['type' => 'string'],
        description: 'L\'identifiant de l\'association dont vous voulez avoir les points.',
        required: true,
    )],
    controller: ApiController::class . '::pointsOfUsersByGuild',
    openapi: new OpenApiOperation(
        responses: [
            '200' => new OpenApiResponse(
                description: 'Chaque utiliisateur, et son nombre de points dans l\'association.',
                content: new ArrayObject([
                    'application/json' => new MediaType(schema: new ArrayObject([
                        'type' => 'object',
                        'properties' => [
                            'guildId' => ['type' => 'string'],
                            'users' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'fullName' => ['type' => 'string'],
                                        'points' => ['type' => 'number'],
                                    ],
                                ],
                            ],
                        ],
                    ])),
                ]),
            ),
        ],
        summary: 'Donne les points de tous les élèves de l\'association',
    ),
    read: false,
    deserialize: false,
    validate: false,
    serialize: false,
    name: 'points_of_users_by_guild',
    parameters: [
        'classe' => new QueryParameter(
            key: 'classe',
            schema: ['type' => 'string'],
            description: 'Optionnel: restreindre les résultats aux points donnés aux étudiants de cette classe (par exemple, 3SI1).',
            required: false,
        ),
        'date' => new QueryParameter(
            filter: new DateFilter(),
            property: 'date',
        ),
        'slug' => new QueryParameter(
            key: 'slug',
            schema: ['type' => 'string'],
            description: 'Optionnel: quand il est fourni, le paramètre guildId est ignoré, au profit de l\'utilisation du slug.',
            required: false,
        ),
    ],
)]
#[Get(openapi: new OpenApiOperation(
    responses: [
        '200' => new OpenApiResponse(description: 'Le point demandé.'),
        '404' => new OpenApiResponse(description: 'Aucun point ne correspond à cet identifiant.'),
        '500' => new OpenApiResponse(description: 'Erreur interne du serveur.'),
    ],
    summary: 'Récupère un point.',
))]
#[GetCollection(
    openapi: new OpenApiOperation(
        responses: [
            '200' => new OpenApiResponse(description: 'La liste des points.'),
            '500' => new OpenApiResponse(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Liste les points auxquels vous avez accès.',
    ),
    parameters: [
        'guild_id' => new QueryParameter(
            key: 'guild_id',
            description: 'Optionnel : restreindre le résultat à l\'une de vos associations. Récupère toutes les associations par défaut.',
            required: false,
        ),
        'classe' => new QueryParameter(
            key: 'classe',
            schema: ['type' => 'string'],
            description: 'Optionnel: restreindre les résultats aux points donnés aux étudiants de cette classe (par exemple, 3SI1).',
            required: false,
        ),
        'date' => new QueryParameter(
            filter: new DateFilter(),
            property: 'date',
        ),
        'slug' => new QueryParameter(
            key: 'slug',
            schema: ['type' => 'string'],
            description: 'Optionnel: quand il est fourni, le paramètre guildId est ignoré, au profit de l\'utilisation du slug.',
            required: false,
        ),
    ])]
#[Delete(
    security: "is_granted('ROLE_SUPER_ADMIN')",
    openapi: new OpenApiOperation(
        summary: 'Supprime un point.',
        responses: [
            '204' => new OpenApiResponse(description: 'Le point a été supprimé.'),
            '403' => new OpenApiResponse(description: 'Accès refusé : réservé aux super-administrateurs.'),
            '404' => new OpenApiResponse(description: 'Aucun point ne correspond à cet identifiant.'),
            '500' => new OpenApiResponse(description: 'Erreur interne du serveur.'),
        ],
    ),
)]
#[Patch(
    securityPostDenormalize: "is_granted('ROLE_SUPER_ADMIN') or user.hasGuild(object.getGuildId())",
    openapi: new OpenApiOperation(
        summary: 'Modifie partiellement un point.',
        responses: [
            '200' => new OpenApiResponse(description: 'Le point a été modifié.'),
            '400' => new OpenApiResponse(description: 'Requête invalide (corps mal formé ou types incorrects).'),
            '404' => new OpenApiResponse(description: 'Aucun point ne correspond à cet identifiant.'),
            '422' => new OpenApiResponse(description: 'Les données ne respectent pas les contraintes de validation.'),
            '500' => new OpenApiResponse(description: 'Erreur interne du serveur.'),
        ],
    ),
)]
#[Post(
    securityPostDenormalize: "is_granted('ROLE_SUPER_ADMIN') or user.hasGuild(object.getGuildId())",
    openapi: new OpenApiOperation(
        summary: 'Crée un point.',
        responses: [
            '201' => new OpenApiResponse(description: 'Le point a été créé.'),
            '400' => new OpenApiResponse(description: 'Requête invalide (corps mal formé ou types incorrects).'),
            '422' => new OpenApiResponse(description: 'Les données ne respectent pas les contraintes de validation.'),
            '500' => new OpenApiResponse(description: 'Erreur interne du serveur.'),
        ],
    ),
)]
#[Put(
    securityPostDenormalize: "is_granted('ROLE_SUPER_ADMIN') or user.hasGuild(object.getGuildId())",
    openapi: new OpenApiOperation(
        summary: 'Remplace un point.',
        responses: [
            '200' => new OpenApiResponse(description: 'Le point a été remplacé.'),
            '400' => new OpenApiResponse(description: 'Requête invalide (corps mal formé ou types incorrects).'),
            '404' => new OpenApiResponse(description: 'Aucun point ne correspond à cet identifiant.'),
            '422' => new OpenApiResponse(description: 'Les données ne respectent pas les contraintes de validation.'),
            '500' => new OpenApiResponse(description: 'Erreur interne du serveur.'),
        ],
    ),
)]
class Point
{
    public const BATCH_RESPONSE_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'created' => ['type' => 'integer'],
            'points' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'email' => ['type' => 'string'],
                        'points' => ['type' => 'number'],
                        'reason' => ['type' => 'string', 'nullable' => true],
                        'guildId' => ['type' => 'string'],
                        'date' => ['type' => 'string', 'format' => 'date-time'],
                    ],
                ],
            ],
        ],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(description: 'Identifiant unique du point.', example: 1)]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'points')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'User cannot be null')]
    #[ApiProperty(description: "Utilisateur qui reçoit les points (IRI de l'utilisateur).", example: '/api/users/1')]
    private ?User $user = null;


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'Reason cannot be longer than {{ limit }} characters',
    )]
    #[ApiProperty(description: "Motif de l'attribution des points (1000 caractères maximum).", example: 'Aide à la mise en place de la soirée')]
    private ?string $reason = null;

    #[Assert\NotNull(message: 'Date cannot be null')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[ApiProperty(description: "Date et heure d'attribution des points, au format ISO 8601.", example: '2030-01-01T18:00:00+00:00')]
    private ?DateTime $date;

    #[Assert\NotNull(message: 'Points cannot be null')]
    #[ORM\Column]
    #[ApiProperty(description: 'Nombre de points attribués (peut être négatif ou décimal).', example: 1.5)]
    private ?float $points = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: false)]
    #[Assert\NotNull(message: 'Guild ID cannot be null')]
    #[Assert\Length(max: 50)]
    #[ApiProperty(description: "Identifiant Discord de la guilde (association) qui attribue les points. Vous devez gérer cette guilde.", example: '1411453838774370397')]
    private string $guild_id;

    public function getGuildId(): string
    {
        return $this->guild_id;
    }

    public function setGuildId(string $guild_id): self
    {
        $this->guild_id = $guild_id;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->date = new DateTime();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

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

    public function getPoints(): ?float
    {
        return $this->points;
    }

    public function setPoints(float $points): static
    {
        $this->points = $points;

        return $this;
    }
}
