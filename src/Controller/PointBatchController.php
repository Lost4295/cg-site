<?php

namespace App\Controller;

use App\Entity\Point;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PointBatchController extends AbstractController
{
    public const MAX_ROWS = 500;
    private const MAX_CSV_BYTES = 1_048_576;
    private const CSV_HEADER = ['mail', 'point', 'raison'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function batch(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new BadRequestHttpException('Invalid JSON body.');
        }

        $members = $data['members'] ?? null;
        if (!is_array($members) || [] === $members || !array_is_list($members)) {
            throw new BadRequestHttpException('"members" must be a non-empty list of emails.');
        }
        if (!is_numeric($data['points'] ?? null)) {
            throw new BadRequestHttpException('"points" must be a number.');
        }
        $reason = $data['reason'] ?? "Aucune raison fournie";
        if (null !== $reason && !is_string($reason)) {
            throw new BadRequestHttpException('"reason" must be a string.');
        }
        $guildId = $data['guild_id']  ??null;
        if (null !== $guildId && !is_string($guildId)) {
            throw new BadRequestHttpException('"guild_id" must be a string.');
        }

        $rows = [];
        foreach ($members as $index => $email) {
            $rows[] = ['line' => $index + 1, 'email' => $email, 'points' => (float) $data['points'], 'reason' => $reason];
        }

        return $this->createPoints($rows, $this->resolveGuildId($guildId));
    }

    public function batchCsv(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new BadRequestHttpException('A CSV file must be sent in the "file" multipart field.');
        }
        if ($file->getSize() > self::MAX_CSV_BYTES) {
            throw new BadRequestHttpException('The CSV file is too large.');
        }
        $guildId = $request->request->get('guild_id');
        if (null !== $guildId && !is_string($guildId)) {
            throw new BadRequestHttpException('"guild_id" must be a string.');
        }

        $rows = $this->parseCsv($file);

        return $this->createPoints($rows, $this->resolveGuildId($guildId));
    }

    /** @return list<array{line: int, email: mixed, points: mixed, reason: ?string}> */
    private function parseCsv(UploadedFile $file): array
    {
        $content = (string) file_get_contents($file->getPathname());
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $lines = preg_split('/\r\n|\n|\r/', $content);
        $lines = array_values(array_filter($lines, static fn (string $line): bool => '' !== trim($line)));
        if ([] === $lines) {
            throw new BadRequestHttpException('The CSV file is empty.');
        }

        $delimiter = substr_count($lines[0], ';') > substr_count($lines[0], ',') ? ';' : ',';
        $header = array_map(static fn (?string $h): string => strtolower(trim((string) $h)), str_getcsv($lines[0], $delimiter, '"', ''));
        if (self::CSV_HEADER !== $header) {
            throw new BadRequestHttpException('The CSV header must be exactly: '.implode(',', self::CSV_HEADER).'.');
        }

        $dataLines = array_slice($lines, 1);
        if ([] === $dataLines) {
            throw new BadRequestHttpException('The CSV file contains no data row.');
        }
        if (count($dataLines) > self::MAX_ROWS) {
            throw new BadRequestHttpException(sprintf('A batch cannot contain more than %d rows.', self::MAX_ROWS));
        }

        $rows = [];
        foreach ($dataLines as $index => $line) {
            $line = str_getcsv($line, $delimiter, '"', '');
            $lineNumber = $index + 2;
            if (3 !== count($line)) {
                $rows[] = ['line' => $lineNumber, 'email' => null, 'points' => null, 'reason' => null, 'invalid' => 'Expected exactly 3 columns.'];
                continue;
            }
            $points = str_replace(',', '.', trim((string) $line[1]));
            $reason = trim((string) $line[2]);
            $rows[] = [
                'line' => $lineNumber,
                'email' => trim((string) $line[0]),
                'points' => is_numeric($points) ? (float) $points : null,
                'reason' => '' === $reason ? "Aucune raison fournie" : $reason,
            ];
        }

        return $rows;
    }

    private function resolveGuildId(?string $guildId): string
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (null === $guildId || '' === $guildId) {
            $guilds = $user->getGuildIds();
            if (1 !== count($guilds)) {
                throw new BadRequestHttpException('"guild_id" is required.');
            }

            return $guilds[0];
        }

        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$user->hasGuild($guildId)) {
            throw $this->createAccessDeniedException('You do not belong to this guild.');
        }

        return $guildId;
    }

    /** @param list<array{line: int, email: mixed, points: mixed, reason: ?string, invalid?: string}> $rows */
    private function createPoints(array $rows, string $guildId): JsonResponse
    {
        if (count($rows) > self::MAX_ROWS) {
            throw new BadRequestHttpException(sprintf('A batch cannot contain more than %d rows.', self::MAX_ROWS));
        }

        $emails = [];
        foreach ($rows as $row) {
            if (is_string($row['email']) && '' !== $row['email']) {
                $emails[] = strtolower($row['email']);
            }
        }
        $usersByEmail = [];
        foreach ($this->users->findBy(['email' => array_values(array_unique($emails))]) as $found) {
            $usersByEmail[strtolower((string) $found->getEmail())] = $found;
        }

        $errors = [];
        $points = [];
        foreach ($rows as $row) {
            $error = $row['invalid'] ?? null;
            $user = is_string($row['email']) ? ($usersByEmail[strtolower($row['email'])] ?? null) : null;
            if (null === $error && null === $user) {
                $error = 'Unknown user.';
            }
            if (null === $error && !is_float($row['points'])) {
                $error = 'Invalid points value.';
            }
            if (null === $error) {
                $point = (new Point())
                    ->setUser($user)
                    ->setPoints($row['points'])
                    ->setReason($row['reason'])
                    ->setGuildId($guildId);
                $violations = $this->validator->validate($point);
                if (count($violations) > 0) {
                    $error = $violations[0]->getMessage();
                } else {
                    $points[] = $point;
                }
            }
            if (null !== $error) {
                $errors[] = ['line' => $row['line'], 'email' => is_string($row['email']) ? $row['email'] : null, 'error' => $error];
            }
        }

        if ([] !== $errors) {
            return new JsonResponse(['message' => 'No point was created: the batch contains errors.', 'errors' => $errors], 422);
        }

        foreach ($points as $point) {
            $this->em->persist($point);
        }
        $this->em->flush();

        return new JsonResponse([
            'created' => count($points),
            'points' => array_map(static fn (Point $p): array => [
                'id' => $p->getId(),
                'email' => $p->getUser()?->getEmail(),
                'points' => $p->getPoints(),
                'reason' => $p->getReason(),
                'guildId' => $p->getGuildId(),
                'date' => $p->getDate()?->format(\DATE_ATOM),
            ], $points),
        ], 201);
    }
}
