<?php
class EventPrime_sanitizer {
    
    public function sanitize_request( $post, $identifier, $exclude = array() ) {
		
		$post = $this->remove_magic_quotes( $post );

		foreach ( $post as $key => $value ) {
			if ( ! in_array( $key, $exclude ) ) {
				if ( ! is_array( $value ) ) {
					$data[ $key ] = $this->get_sanitized_fields( $identifier, $key, $value );
				} else {
                                    
                    $data[ $key ] = maybe_serialize( $this->sanitize_request_array( $value, $identifier ) );
					
				}
			}
		}

		if ( isset( $data ) ) {
			return $data; } else {
			return null; }
	}
        
    public function sanitize_request_array( $post, $identifier ) {
		
		foreach ( $post as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = $this->sanitize_request_array( $value, $identifier );
			} else {
				$data[ $key ] = $this->get_sanitized_fields( $identifier, $key, $value );
			}
		}

		if ( isset( $data ) ) {
			return $data; } else {
			return null; }
	}
        


	public function get_sanitized_fields( $identifier, $field, $value ) {
		$sanitize_method = 'get_sanitized_' . strtolower( $identifier ) . '_field';

		if ( method_exists( $this, $sanitize_method ) ) {
			$sanitized_value = $this->$sanitize_method( $field, $value );
		} else {
			$classname = "EP_Helper_$identifier";
		}

		if ( isset( $classname ) && class_exists( $classname ) ) {
			$externalclass   = new $classname();
			$sanitized_value = $externalclass->get_sanitized_fields( $identifier, $field, $value );
		}

		return $sanitized_value;
	}

	public function get_sanitized_checkout_fields_field( $field, $value ) {
		switch ( $field ) {
			case 'id':
				$value = sanitize_text_field( $value );
				break;
            case 'option_data':
				$value = wp_kses_post( $value );
				break;
			default:
				$value = sanitize_text_field( $value );

		}
		return $value;
	}
        
        public function get_sanitized_ticket_categories_field( $field, $value ) {
		switch ( $field ) {
			case 'id':
				$value = sanitize_text_field( $value );
				break;
			default:
				$value = sanitize_text_field( $value );

		}
			return $value;
	}
        
        public function get_sanitized_ticket_field( $field, $value ) {
		switch ( $field ) {
			case 'description':
				$value = wp_kses_post( $value );
				break;
                        case 'visibility':
				$value = wp_kses_post( $value );
				break;
                        case 'offers':
				$value = wp_kses_post( $value );
				break;
                        case 'booking_starts':
				$value = wp_kses_post( $value );
				break;
                        case 'booking_ends':
				$value = wp_kses_post( $value );
				break;
			default:
				$value = sanitize_text_field( $value );

		}
			return $value;
	}


	public function remove_magic_quotes( $input ) {
		foreach ( $input as $key => $value ) {
			if ( is_array( $value ) ) {
				$input[ $key ] = $this->remove_magic_quotes( $value );
			} elseif ( is_string( $value ) ) {
				$input[ $key ] = stripslashes( $value );
			}
		}
		return $input;
	}

	public function sanitize( $input ) {
            // Initialize the new array or object that will hold the sanitized values
            if (is_array($input)) {
                $new_input = array();
            } elseif (is_object($input)) {
                $new_input = new stdClass();
            } else {
                return $input; // If it's not an array or object, return the input as is
            }
            $email_field_array = $this->email_field_keys_array();
            // Loop through the input and sanitize each of the values
            foreach ( $input as $key => $val ) {
                if (empty($val)) {
                    if (is_array($new_input)) {
                        $new_input[ $key ] = $val;
                    } else {
                        $new_input->{$key} = $val;
                    }
                    continue;
                }

                if ( is_array( $val ) || is_object( $val ) ) {
                    // Recursively sanitize arrays or objects
                    if (is_array($new_input)) {
                        $new_input[ $key ] = $this->sanitize( $val );
                    } else {
                        $new_input->{$key} = $this->sanitize( $val );
                    }
                } else {
                    // Sanitize scalar values based on the key
                    
                    switch ( $key ) {
                        case 'login':
                        case 'uname':
                            $sanitized_value = sanitize_user( $val );
                            break;
                        case 'user_email':
                            $sanitized_value = sanitize_email( $val );
                            break;
                        case 'key':
                            $sanitized_value = sanitize_text_field( wp_strip_all_tags($val) );
                            break;
                        case 'nonce':
                        case '_wpnonce':
                        case 'security':
                            $sanitized_value = sanitize_key( $val );
                            break;
                        case 'user_login':
                        case 'userdata':
                            if ( is_email( $val ) ) {
                                $sanitized_value = sanitize_email( $val );
                            } else {
                                $sanitized_value = sanitize_user( $val );
                            }
                            break;
                        default:
                            if ( is_email( $val ) ) {
                                $sanitized_value = sanitize_email( $val );
                            } 
                            elseif(in_array($key,$email_field_array))
                            {
                                $sanitized_value = wp_kses_post( $val );
                            }
                            else
                            {
                                $val = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
                                $sanitized_value = sanitize_text_field( wp_strip_all_tags($val) );
                            }
                            break;
                    }

                    // Assign sanitized value back to the new input array or object
                    if (is_array($new_input)) {
                        $new_input[ $key ] = $sanitized_value;
                    } else {
                        $new_input->{$key} = $sanitized_value;
                    }
                }
            }

            return $new_input;
        }
        
        public function email_field_keys_array()
        {
            $fields = array(
                'send_booking_cancellation_email',
                'booking_cancelation_email_subject',
                'booking_cancelation_email',
                'booking_cancelation_email_cc',
                'send_booking_confirm_email',
                'booking_confirm_email_subject',
                'booking_confirmed_email',
                'send_admin_booking_confirm_email',
                'admin_booking_confirmed_email_subject',
                'admin_booking_confirmed_email',
                'admin_booking_confirmed_email_cc',
                'admin_booking_confirm_email_attendees',
                'send_booking_pending_email',
                'booking_pending_email_subject',
                'booking_pending_email',
                'booking_pending_email_cc',
                'send_booking_pending_admin_email',
                'booking_pending_admin_email_subject',
                'booking_pending_admin_email',
                'booking_pending_admin_email_cc',
                'send_booking_refund_email',
                'booking_refund_email_subject',
                'booking_refund_email',
                'booking_refund_email_cc',
                'send_event_approved_email',
                'event_approved_email_subject',
                'event_approved_email',
                'send_event_submitted_email',
                'event_submitted_email_subject',
                'event_submitted_email',
                'event_submitted_email_cc',
                'registration_email_subject',
                'registration_email_content',
                'reset_password_mail_subject',
                'reset_password_mail',
                'send_certificate_event_invitation',
                'certificate_event_invitation_subject',
                'certificate_event_invitation_email',
                'send_rsvp_event_invitation',
                'rsvp_event_invitation_subject',
                'rsvp_event_invitation_email',
                'send_event_wt_booking_mail',
                'event_wt_booking_mail_subject',
                'event_wt_booking_mail_email',
                'send_event_wt_reg_mail',
                'event_wt_reg_mail_subject',
                'event_wt_reg_mail_email'
            );
            $fields = apply_filters('ep_email_field_keys_array', $fields);
            return $fields;
        }


}
