Feature: Finding domains of redirects
  As a user
  I want to find the unique outbound domains
  So I can populate the allowed_redirect_hosts filter

  Background:
    Given a WP installation with the WPCOM Legacy Redirector plugin

  Scenario: Find zero domains when there are no redirects saved
    When I run `wp wpcom-legacy-redirector find-domains`
    Then STDOUT should contain:
      """
      Found 0 unique outbound domains.
      """

  Scenario: Find zero domains when only path redirects are saved
    Given I run `wp wpcom-legacy-redirector insert-redirect /foo /hello-world`
    When I run `wp wpcom-legacy-redirector find-domains`
    Then STDOUT should contain:
      """
      Found 0 unique outbound domains.
      """

  Scenario: Find one domain from one URL redirect
    Given "google.com" is allowed to be redirected

    When I run `wp wpcom-legacy-redirector insert-redirect /foo https://google.com`
    And I run `wp wpcom-legacy-redirector find-domains`
    Then STDOUT should contain:
      """
      Found 1 unique outbound domain.
      google.com
      """

  Scenario: Find one domain from multiple redirects to the same host
    Given "google.com" is allowed to be redirected

    When I run `wp wpcom-legacy-redirector insert-redirect /foo https://google.com`
    And I run `wp wpcom-legacy-redirector insert-redirect /foo1 https://google.com/1`

    When I run `wp wpcom-legacy-redirector find-domains`
    Then STDOUT should contain:
      """
      Found 1 unique outbound domain.
      google.com
      """

  Scenario: Find multiple domains from multiple redirects to different domains
    Given "google.com" is allowed to be redirected
    And "google.co.uk" is allowed to be redirected

    When I run `wp wpcom-legacy-redirector insert-redirect /foo https://google.com`
    And I run `wp wpcom-legacy-redirector insert-redirect /foo1 https://google.co.uk`

    When I run `wp wpcom-legacy-redirector find-domains`
    Then STDOUT should contain:
      """
      Found 2 unique outbound domains.
      google.com
      google.co.uk
      """
