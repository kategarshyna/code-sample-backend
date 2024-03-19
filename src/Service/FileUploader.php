<?php
namespace App\Service;

use App\Entity\Photo;
use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private string $targetDirectory;
    private string $publicDirectory;

    public function __construct(string $targetDirectory, string $publicDirectory)
    {
        $this->targetDirectory = $targetDirectory;
        $this->publicDirectory = $publicDirectory;
    }

    public function upload(UploadedFile $file, User $user): Photo
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = transliterator_transliterate(
            'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
            $originalFilename
        );
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $file->move($this->getTargetDirectory(), $fileName);

        return (new Photo())->setName($fileName)->setUrl($this->getUrl($fileName))->setUser($user);
    }

    public function getTargetDirectory(): string
    {
        return sprintf('%s/%s', $this->publicDirectory, $this->targetDirectory);
    }

    public function getUrl(string $fileName): string
    {
        return sprintf('/%s/%s', $this->targetDirectory, $fileName);
    }
}