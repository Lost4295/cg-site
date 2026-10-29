<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use App\Controller\ApiController;

#[ApiResource(shortName: 'Classes', description: "Cette ressource est utilisée pour récupérer la liste des classes existantes.
    Elle est accessible à tous les utilisateurs authentifiés."
)]
#[GetCollection(
    uriTemplate: '/classes',
    outputFormats: [
        'json' => ['application/json'],
    ],
    controller: ApiController::class . '::classes',
    openapi: new OpenApiOperation(
        responses: [
            '200' => new OpenApiResponse(
                description: 'Une liste des classes existantes',
                content: new \ArrayObject([
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'label' => [
                                        'type' => 'string',
                                        'description' => 'Nom de la classe',
                                        'example' => '1A1',
                                    ],
                                    'description' => [
                                        'type' => 'string',
                                        'description' => 'Description de la classe',
                                        'example' => 'Première année Alternance Groupe 1',
                                    ],
                                    'value' => [
                                        'type' => 'string',
                                        'description' => 'Valeur de la classe',
                                        'example' => '1A1',
                                    ],
                                    'emoji' => [
                                        'type' => 'string',
                                        'description' => 'Emoji associé à la classe',
                                        'example' => '1️⃣',
                                    ],
                                ],
                                'required' => [
                                    'label',
                                    'description',
                                    'value',
                                ],
                            ],
                        ],
                        'example' => [
                            [
                                'label' => '1A1',
                                'description' => 'Première année Alternance Groupe 1',
                                'value' => '1A1',
                                'emoji' => '1️⃣',
                            ],
                        ],
                    ],
                ]),
            ),
        ],
        summary: 'Liste les classes existantes',
    ),
    paginationEnabled: false,
    read: false,
    deserialize: false,
    validate: false,
    serialize: false,
    name: 'classes_collection'
)]
final class ClassesResource
{
    #[ApiProperty(
        description: "Champ court pour désigner la classe.",
        openapiContext: [
            'type' => 'string',
            'example' => '2A4'
        ]
    )]
    public string $label;
    #[ApiProperty(
        description: "Titre nom de la classe",
        openapiContext: [
            'type' => 'string',
            'example' => 'Deuxième année Alternance Groupe 1',
        ]
    )]
    public string $description;
    #[ApiProperty(
        description: "Champ court désignant également la classe.",
        openapiContext: [
            'type' => 'string',
            'example' => '2A4'
        ]
    )]
    public string $value;
    #[ApiProperty(
        description: "Emoji utilisé par l'API pour grouper les classes par niveau.",
        openapiContext: [
            'type' => 'string',
            'example' => '1️⃣'
        ]
    )]
    public string $emoji;
}
