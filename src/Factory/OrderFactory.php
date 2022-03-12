<?php

namespace App\Factory;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Study;
use DateTime;

/**
 * Class OrderFactory.
 */
class OrderFactory
{
    /**
     * Creates an order.
     */
    public function create(): Order
    {
        $order = new Order();
        $order
            ->setStatus(Order::STATUS_CART)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        return $order;
    }

    /**
     * Creates an item for a product.
     */
    public function createItem(Study $study): OrderItem
    {
        $item = new OrderItem();
        $item->setProduct($study);
        $item->setQuantity(1);

        return $item;
    }
}
