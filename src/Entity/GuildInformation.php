<?php

namespace App\Entity;

use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use App\Repository\GuildResolverRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Ignore;


#[ORM\Entity(repositoryClass: GuildResolverRepository::class)]
#[ORM\Table(name: 'guild_information')]
#[ApiResource(
    shortName: 'Informations sur les Associations',
    description:"Cette ressource donne des informations sur les serveurs enregistrés par l'API. Elle donne notamment leur nom, et l'id Discord leur correspondant.",
    normalizationContext: ['groups' => ['user:read']]
)]
#[Get(
    uriTemplate: '/guild_informations/{guild_id}',
    uriVariables: ['guild_id'],
    openapi: new Operation(
        responses: [
            '200' => new Response(description: 'Les informations de la guilde.'),
            '404' => new Response(description: 'Aucune guilde ne correspond à cet identifiant.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Récupère une guilde par son identifiant.',
    ),
)]
#[GetCollection(
    uriTemplate: '/guild_informations/',
    openapi: new Operation(
        responses: [
            '200' => new Response(description: 'La liste des guildes.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Liste les guildes.',
    ),
)]
#[Post(
    uriTemplate: '/guild_informations',
    openapi: new Operation(
        responses: [
            '201' => new Response(description: 'La guilde a été créée.'),
            '400' => new Response(description: 'Requête invalide (corps mal formé ou types incorrects).'),
            '422' => new Response(description: 'Les données ne respectent pas les contraintes de validation.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Crée une guilde.',
    ),
    security: "is_granted('ROLE_SUPER_ADMIN')",
)]
class GuildInformation
{

    #[ORM\Id]
    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[ApiProperty(
        description: "Identifiant Discord de la guilde (association).",
        identifier: true,
        openapiContext: [
            'type' => 'string',
            'example' => '1411453838774370397'
        ]
    )]
    #[Groups(['user:read'])]
    private ?string $guild_id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['user:read'])]
    #[ApiProperty(description: "Nom affiché de la guilde (association).", example: 'BDE Informatique')]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    #[Groups(['user:read'])]
    #[ApiProperty(description: "Identifiant court et lisible de la guilde, utilisable à la place du guild_id dans les URL pour rechercher des points.", example: 'bde-info')]
    private ?string $slug = null;


    #[ApiProperty(
        description: "Identifiant Discord de la guilde (association). Champ généré automatiquement.",
        identifier: true,
        openapiContext: [
            'type' => 'string',
            'example' => '1411453838774370397'
        ]
    )]
    #[Groups(['user:read'])]
    public function getId(): ?string
    {
        return $this->guild_id;
    }

    #[Ignore]
    public function getGuildId(): ?string
    {
        return $this->guild_id;
    }

    public function setGuildId(string $guild_id): static
    {
        $this->guild_id = $guild_id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }
}
