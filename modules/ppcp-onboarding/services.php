<?php
/**
 * The onboarding module services.
 *
 * @package WooCommerce\PayPalCommerce\Onboarding
 */

declare(strict_types=1);

namespace WooCommerce\PayPalCommerce\Onboarding;

use Dhii\Data\Container\ContainerInterface;
use WooCommerce\PayPalCommerce\ApiClient\Authentication\Bearer;
use WooCommerce\PayPalCommerce\ApiClient\Authentication\ConnectBearer;
use WooCommerce\PayPalCommerce\ApiClient\Authentication\PayPalBearer;
use WooCommerce\PayPalCommerce\ApiClient\Endpoint\LoginSeller;
use WooCommerce\PayPalCommerce\ApiClient\Endpoint\PartnerReferrals;
use WooCommerce\PayPalCommerce\ApiClient\Helper\Cache;
use WooCommerce\PayPalCommerce\Onboarding\Assets\OnboardingAssets;
use WooCommerce\PayPalCommerce\Onboarding\Endpoint\LoginSellerEndpoint;
use WooCommerce\PayPalCommerce\Onboarding\Render\OnboardingRenderer;
use WpOop\TransientCache\CachePoolFactory;

return array(
	'api.sandbox-host'                          => static function ( $container ): string {

		$state       = $container->get( 'onboarding.state' );

		/**
		 * The Environment and State variables.
		 *
		 * @var Environment $environment
		 * @var State $state
		 */
		if ( $state->current_state() >= State::STATE_ONBOARDED ) {
			return 'https://api.sandbox.paypal.com';
		}
		// ToDo: Real connect.woocommerce.com sandbox link.
		return 'http://connect-woo.wpcust.com';
	},
	'api.production-host'                       => static function ( $container ): string {

		$state       = $container->get( 'onboarding.state' );

		/**
		 * The Environment and State variables.
		 *
		 * @var Environment $environment
		 * @var State $state
		 */
		if ( $state->current_state() >= State::STATE_ONBOARDED ) {
			return 'https://api.paypal.com';
		}
		// ToDo: Real connect.woocommerce.com production link.
		return 'http://connect-woo.wpcust.com';
	},
	'api.host'                                  => static function ( $container ): string {
		$environment = $container->get( 'onboarding.environment' );

		/**
		 * The Environment and State variables.
		 *
		 * @var Environment $environment
		 */
		return $environment->current_environment_is( Environment::SANDBOX )
			? (string) $container->get( 'api.sandbox-host' ) : (string) $container->get( 'api.production-host' );

	},
	'api.paypal-host-production'                => static function( $container ) : string {
		return 'https://api.paypal.com';
	},
	'api.paypal-host-sandbox'                   => static function( $container ) : string {
		return 'https://api.sandbox.paypal.com';
	},
	'api.paypal-host'                           => function( $container ) : string {
		$environment = $container->get( 'onboarding.environment' );
		/**
		 * The current environment.
		 *
		 * @var Environment $environment
		 */
		if ( $environment->current_environment_is( Environment::SANDBOX ) ) {
			return $container->get( 'api.paypal-host-sandbox' );
		}
		return $container->get( 'api.paypal-host-production' );

	},

	'api.bearer'                                => static function ( $container ): Bearer {

		$state = $container->get( 'onboarding.state' );

		/**
		 * The State.
		 *
		 * @var State $state
		 */
		if ( $state->current_state() < State::STATE_ONBOARDED ) {
			return new ConnectBearer();
		}
		$cache  = new Cache( 'ppcp-paypal-bearer' );
		$key    = $container->get( 'api.key' );
		$secret = $container->get( 'api.secret' );

		$host   = $container->get( 'api.host' );
		$logger = $container->get( 'woocommerce.logger.woocommerce' );
		return new PayPalBearer(
			$cache,
			$host,
			$key,
			$secret,
			$logger
		);
	},
	'onboarding.state'                          => function( $container ) : State {
		$environment = $container->get( 'onboarding.environment' );
		$settings    = $container->get( 'wcgateway.settings' );
		return new State( $environment, $settings );
	},
	'onboarding.environment'                    => function( $container ) : Environment {
		$settings = $container->get( 'wcgateway.settings' );
		return new Environment( $settings );
	},

	'onboarding.assets'                         => function( $container ) : OnboardingAssets {
		$state                 = $container->get( 'onboarding.state' );
		$login_seller_endpoint = $container->get( 'onboarding.endpoint.login-seller' );
		return new OnboardingAssets(
			$container->get( 'onboarding.url' ),
			$state,
			$login_seller_endpoint
		);
	},

	'onboarding.url'                            => static function ( $container ): string {
		return plugins_url(
			'/modules/ppcp-onboarding/',
			dirname( __FILE__, 3 ) . '/woocommerce-paypal-commerce-gateway.php'
		);
	},

	'api.endpoint.login-seller-production'      => static function ( $container ) : LoginSeller {

		$logger = $container->get( 'woocommerce.logger.woocommerce' );
		return new LoginSeller(
			$container->get( 'api.paypal-host-production' ),
			$container->get( 'api.partner_merchant_id' ),
			$logger
		);
	},

	'api.endpoint.login-seller-sandbox'         => static function ( $container ) : LoginSeller {

		$logger = $container->get( 'woocommerce.logger.woocommerce' );
		return new LoginSeller(
			$container->get( 'api.paypal-host-sandbox' ),
			$container->get( 'api.partner_merchant_id' ),
			$logger
		);
	},

	'onboarding.endpoint.login-seller'          => static function ( $container ) : LoginSellerEndpoint {

		$request_data            = $container->get( 'button.request-data' );
		$login_seller_production = $container->get( 'api.endpoint.login-seller-production' );
		$login_seller_sandbox    = $container->get( 'api.endpoint.login-seller-sandbox' );
		$partner_referrals_data  = $container->get( 'api.repository.partner-referrals-data' );
		$settings                = $container->get( 'wcgateway.settings' );

		$cache = new Cache( 'ppcp-paypal-bearer' );
		return new LoginSellerEndpoint(
			$request_data,
			$login_seller_production,
			$login_seller_sandbox,
			$partner_referrals_data,
			$settings,
			$cache
		);
	},
	'api.endpoint.partner-referrals-sandbox'    => static function ( $container ) : PartnerReferrals {

		// ToDo: Real connect sandbox URL.
		$host_url = 'http://connect-woo.wpcust.com';
		return new PartnerReferrals(
			$host_url,
			new ConnectBearer(),
			$container->get( 'api.repository.partner-referrals-data' ),
			$container->get( 'woocommerce.logger.woocommerce' )
		);
	},
	'api.endpoint.partner-referrals-production' => static function ( $container ) : PartnerReferrals {
		// ToDo: Real connect production URL.
		$host_url = 'http://connect-woo.wpcust.com';
		return new PartnerReferrals(
			$host_url,
			new ConnectBearer(),
			$container->get( 'api.repository.partner-referrals-data' ),
			$container->get( 'woocommerce.logger.woocommerce' )
		);
	},
	'onboarding.render'                         => static function ( $container ) : OnboardingRenderer {

		$partner_referrals         = $container->get( 'api.endpoint.partner-referrals-production' );
		$partner_referrals_sandbox = $container->get( 'api.endpoint.partner-referrals-sandbox' );
		$settings                  = $container->get( 'wcgateway.settings' );
		return new OnboardingRenderer(
			$settings,
			$partner_referrals,
			$partner_referrals_sandbox
		);
	},
);
