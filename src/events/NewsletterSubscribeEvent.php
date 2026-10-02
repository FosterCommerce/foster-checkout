<?php

namespace fostercommerce\fostercheckout\events;

use craft\commerce\elements\Order;
use yii\base\Event;

/**
 * Event for subscribing a customer who ticked the checkout's newsletter checkbox.
 *
 * Set `handled` to true once subscribed, so the checkout's Klaviyo Connect subscription is skipped.
 *
 * @since 2.0.0
 */
class NewsletterSubscribeEvent extends Event
{
	public Order $order;

	public string $email;

	/**
	 * The **Newsletter list ID** setting, with any environment variable resolved. Null when blank.
	 */
	public ?string $listId = null;
}
