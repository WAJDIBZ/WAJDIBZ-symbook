<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class BookImageExtension extends AbstractExtension
{
    public function __construct(private readonly Packages $packages)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('book_image_url', [$this, 'bookImageUrl']),
        ];
    }

    public function bookImageUrl(?string $image, string $defaultUploadPrefix = 'uploads/books/'): string
    {
        $image = $image !== null ? trim($image) : '';

        if ($image === '') {
            return $this->packages->getUrl('images/book-placeholder.svg');
        }

        if (preg_match('#^https?://#i', $image) === 1) {
            return $image;
        }

        // Already an absolute path on this host.
        if (str_starts_with($image, '/')) {
            return $image;
        }

        // Already a web path.
        if (str_starts_with($image, 'uploads/')) {
            return $this->packages->getUrl($image);
        }

        $prefix = rtrim($defaultUploadPrefix, '/').'/';

        return $this->packages->getUrl($prefix.$image);
    }
}
