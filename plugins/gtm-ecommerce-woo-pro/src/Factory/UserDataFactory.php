<?php

namespace GtmEcommerceWooPro\Lib\Factory;

use WC_Order;
use WP_User;

class UserDataFactory {

	public function create( $source ) {
		if ( $source instanceof WP_User ) {
			return $this->createFromUser( $source );
		}

		if ( $source instanceof WC_Order ) {
			return $this->createFromOrder( $source );
		}

		return [];
	}

	private function createFromUser( WP_User $user ) {
		$address1 = get_user_meta( $user->ID, 'billing_address_1', true );
		$address2 = get_user_meta( $user->ID, 'billing_address_2', true );

		$email = $user->user_email;
		$phone = get_user_meta( $user->ID, 'billing_phone', true );

		$userData = [
			'email'        => $email,
			'phone'        => $phone,
			'address'      => [
				'first_name' => get_user_meta( $user->ID, 'billing_first_name', true ),
				'last_name'  => get_user_meta( $user->ID, 'billing_last_name', true ),
				'street'     => trim( join( ' ', array_filter( [ $address1, $address2 ] ) ) ),
				'city'       => get_user_meta( $user->ID, 'billing_city', true ),
				'region'      => get_user_meta( $user->ID, 'billing_state', true ),
				'postal_code'   => get_user_meta( $user->ID, 'billing_postcode', true ),
				'country'    => get_user_meta( $user->ID, 'billing_country', true ),
			],
		];

		return $this->enrichWithHashes( $userData );
	}

	private function createFromOrder( WC_Order $order ) {
		$email = $order->get_billing_email();
		$phone = $order->get_billing_phone();

		$userData = [
			'email'   => $email,
			'phone'   => $phone,
			'address' => [
				'first_name'  => $order->get_billing_first_name(),
				'last_name'   => $order->get_billing_last_name(),
				'street'      => trim( join( ' ', array_filter( [ $order->get_billing_address_1(), $order->get_billing_address_2() ] ) ) ),
				'postal_code' => $order->get_billing_postcode(),
				'country'     => $order->get_billing_country(),
				'region'      => $order->get_billing_state(),
				'city'        => $order->get_billing_city(),
			],
		];

		return $this->enrichWithHashes( $userData );
	}

	private function enrichWithHashes( array $userData ) {
		if ( ! empty( $userData['email'] ) ) {
			$userData['sha256_email_address'] = hash( 'sha256', $userData['email'] );
		}

		if ( ! empty( $userData['phone'] ) ) {
			$userData['sha256_phone_number'] = hash( 'sha256', $userData['phone'] );
		}

		return $userData;
	}

}

