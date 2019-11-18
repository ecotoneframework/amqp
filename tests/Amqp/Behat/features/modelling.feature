Feature: activating as aggregate order entity


  Scenario: I order product and I want to see it on the list of orders products
    Given I active messaging for namespace "Test\Ecotone\Amqp\Fixture\Order"
    When I order "milk"
    Then there should be nothing on the order list
    When I active receiver "orders"
    Then there should be nothing on the order list
    And I active receiver "order.register.target"
    Then there should be nothing on the order list
    When I active receiver "orders"
    Then on the order list I should see "milk"