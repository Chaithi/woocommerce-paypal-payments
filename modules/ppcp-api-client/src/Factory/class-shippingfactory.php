<?php
/**
 * The shipping factory.
 *
 * @package Inpsyde\PayPalCommerce\ApiClient\Factory
 */

declare(strict_types=1);

namespace Inpsyde\PayPalCommerce\ApiClient\Factory;

use Inpsyde\PayPalCommerce\ApiClient\Entity\Shipping;
use Inpsyde\PayPalCommerce\ApiClient\Exception\RuntimeException;

/**
 * Class ShippingFactory
 */
class ShippingFactory {

	/**
	 * The address factory.
	 *
	 * @var AddressFactory
	 */
	private $address_factory;

	/**
	 * ShippingFactory constructor.
	 *
	 * @param AddressFactory $address_factory The address factory.
	 */
	public function __construct( AddressFactory $address_factory ) {
		$this->address_factory = $address_factory;
	}

	/**
	 * Creates a shipping object based off a Woocommerce customer.
	 *
	 * @param \WC_Customer $customer The Woocommerce customer.
	 *
	 * @return Shipping
	 */
	public function from_wc_customer( \WC_Customer $customer ): Shipping {
		// Replicates the Behavior of \WC_Order::get_formatted_shipping_full_name().
		$full_name = sprintf(
			// translators: %1$s is the first name and %2$s is the second name. wc translation.
			_x( '%1$s %2$s', 'full name', 'paypal-for-woocommerce' ),
			$customer->get_shipping_first_name(),
			$customer->get_shipping_last_name()
		);
		$address = $this->address_factory->from_wc_customer( $customer );
		return new Shipping(
			$full_name,
			$address
		);
	}

	/**
	 * Creates a Shipping object based off a Woocommerce order.
	 *
	 * @param \WC_Order $order The Woocommerce order.
	 *
	 * @return Shipping
	 */
	public function from_wc_order( \WC_Order $order ): Shipping {
		$full_name = $order->get_formatted_shipping_full_name();
		$address   = $this->address_factory->from_wc_order( $order );
		return new Shipping(
			$full_name,
			$address
		);
	}

	/**
	 * Creates a Shipping object based of from the PayPal JSON response.
	 *
	 * @param \stdClass $data The JSON object.
	 *
	 * @return Shipping
	 * @throws RuntimeException When JSON object is malformed.
	 */
	public function from_paypal_response( \stdClass $data ): Shipping {
		if ( ! isset( $data->name->full_name ) ) {
			throw new RuntimeException(
				__( 'No name was given for shipping.', 'paypal-for-woocommerce' )
			);
		}
		if ( ! isset( $data->address ) ) {
			throw new RuntimeException(
				__( 'No address was given for shipping.', 'paypal-for-woocommerce' )
			);
		}
		$address = $this->address_factory->from_paypal_response( $data->address );
		return new Shipping(
			$data->name->full_name,
			$address
		);
	}
}