<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[ORM\Table(name: 'participations')]
#[ApiResource(
    shortName: 'Participations des élèves',
    description: "Cette ressource est utilisée pour noter les participations des élèves à un événement préalablement créé dans la ressource Dates.
Elle est optionnelle, et présente si vous avez besoin de noter les présences des membres à une Date."
)]
#[Get(
    uriTemplate: '/participations/{id}',
    openapi: new Operation(
        responses: [
            '200' => new Response(description: 'La participation demandée.'),
            '404' => new Response(description: 'Aucune participation ne correspond.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Récupère une participation.',
    ))]
#[GetCollection(
    uriTemplate: '/participations',
    openapi: new Operation(
        responses: [
            '200' => new Response(description: 'La liste des participations.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Liste les participations.',
    ),
    parameters: [
        'guild_id' => new QueryParameter(
            key: 'guild_id',
            description: 'Optionnel : limite les résultats à une de vos guildes. Par défaut, toutes vos guildes.',
            required: false,
        ),
    ])]
#[Delete(
    uriTemplate: '/participations/{id}',
    openapi: new Operation(
        responses: [
            '204' => new Response(description: 'La participation a été supprimée.'),
            '403' => new Response(description: 'Accès refusé : réservé aux super-administrateurs.'),
            '404' => new Response(description: 'Aucune participation ne correspond.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Supprime une participation.',
    ),
    security: "is_granted('ROLE_SUPER_ADMIN')",
)]
#[Patch(
    uriTemplate: '/participations/{id}',

    openapi: new Operation(
        responses: [
            '200' => new Response(description: 'La participation a été modifiée.'),
            '400' => new Response(description: 'Requête invalide (corps mal formé ou types incorrects).'),
            '404' => new Response(description: 'Aucune participation ne correspond.'),
            '422' => new Response(description: 'Les données ne respectent pas les contraintes de validation.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Modifie partiellement une participation.',
    ),
    securityPostDenormalize: "is_granted('ROLE_SUPER_ADMIN') or user.hasGuild(object.getGuildId())",
)]
#[Post(
    uriTemplate: '/participations/',
    openapi: new Operation(
        responses: [
            '201' => new Response(description: 'La participation a été créée.'),
            '400' => new Response(description: 'Requête invalide (corps mal formé ou types incorrects).'),
            '422' => new Response(description: 'Les données ne respectent pas les contraintes de validation.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Crée une participation.',
    ),
    securityPostDenormalize: "is_granted('ROLE_SUPER_ADMIN') or user.hasGuild(object.getGuildId())",
)]
#[Put(
    uriTemplate: '/participations/{id}',

    openapi: new Operation(
        responses: [
            '200' => new Response(description: 'La participation a été remplacée.'),
            '400' => new Response(description: 'Requête invalide (corps mal formé ou types incorrects).'),
            '404' => new Response(description: 'Aucune participation ne correspond.'),
            '422' => new Response(description: 'Les données ne respectent pas les contraintes de validation.'),
            '500' => new Response(description: 'Erreur interne du serveur.'),
        ],
        summary: 'Remplace une participation.',
    ),
    securityPostDenormalize: "is_granted('ROLE_SUPER_ADMIN') or user.hasGuild(object.getGuildId())",
)]
class Participation
{

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist',], inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[ApiProperty(description: "Utilisateur qui participe à l'événement (IRI de l'utilisateur).", example: '/api/users/1')]
    private ?User $user = null;

    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'participations', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[ApiProperty(description: "Événement auquel l'utilisateur participe (IRI de la date).", example: '/api/dates/1')]
    private ?Date $date = null;


    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDate(): ?Date
    {
        return $this->date;
    }

    public function setDate(Date $date): static
    {
        $this->date = $date;

        return $this;
    }
}
