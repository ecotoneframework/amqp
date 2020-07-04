Feature: activating as aggregate order entity

  Scenario: I order product and I want to see it on the list of orders products
    Given I active messaging for namespace "Test\Ecotone\Amqp\Fixture\Order"
    When I order "milk"
    Then there should be nothing on the order list
    When I active receiver "orders"
    Then on the order list I should see "milk"

  Scenario: I order with transaction a product with failure, so the order should never be placed
    Given I active messaging for namespace "Test\Ecotone\Amqp\Fixture\FailureTransaction"
    When I transactionally order "milk"
    When I active receiver "placeOrderEndpoint"

  Scenario: I order with transaction a product with success, so the order should be placed
    Given I active messaging for namespace "Test\Ecotone\Amqp\Fixture\SuccessTransaction"
    When I transactionally order "milk"
    And I active receiver "placeOrderEndpoint"
    Then there should be "milk" order
    And I active receiver "placeOrderEndpoint"
    And there should be no next order

  Scenario: I add product to shopping cart with publisher and consume it
    Given I active messaging for namespace "Test\Ecotone\Amqp\Fixture\Shop"
    When I add product "window" to shopping cart
    And I active receiver "addToCart"
    Then there should be product "window" in shopping cart
