<?php

declare(strict_types=1);

namespace Setono\SyliusCalloutPlugin\Assigner;

use Pagerfanta\Pagerfanta;
use Setono\SyliusCalloutPlugin\Message\Command\AssignProductCallouts;
use Setono\SyliusCalloutPlugin\Model\CalloutsAwareInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class CalloutAssigner implements CalloutAssignerInterface
{
    private const PRODUCTS_PER_ASSOCIATION = 100;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var MessageBusInterface */
    private $messageBus;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        MessageBusInterface $messageBus
    ) {
        $this->productRepository = $productRepository;
        $this->messageBus = $messageBus;
    }

    public function assign(): void
    {
        /** @var Pagerfanta $paginator */
        $paginator = $this->productRepository->createPaginator(['enabled' => true]);
        $paginator->setMaxPerPage(self::PRODUCTS_PER_ASSOCIATION);

        for (
            $page = 1, $remainingResults = $paginator->getNbResults();
            0 < $remainingResults;
            $page++, $remainingResults -= self::PRODUCTS_PER_ASSOCIATION
        ) {
            $paginator->setCurrentPage($page);
            /** @var ProductInterface|CalloutsAwareInterface $product */
            $products = $paginator->getCurrentPageResults();
            $productIds = [];

            foreach ($products as $product) {
                $productIds[] = $product->getId();
            }

            $this->messageBus->dispatch(new AssignProductCallouts($productIds));
        }
    }
}