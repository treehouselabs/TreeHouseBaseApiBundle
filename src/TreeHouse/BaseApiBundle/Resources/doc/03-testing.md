## Testing

Basic functionality for Behat testing is supplied. You can load the
contexts for your feature:

```yaml
default:
  suites:
    api:
      paths:   [%paths.base%/features/api]
      contexts:
        - TreeHouse\BaseApiBundle\Behat\ApiContext
        - TreeHouse\BaseApiBundle\Behat\FixtureContext
```

Now create the feature:

```gherkin
# features/articles.feature

Feature: Articles Api
  As an Api user
  I need to be able to perform CRUD operations on articles

  Background:
    Given an api user named "test" with password "1234" exists
    And I have a valid token for "test" with password "1234"
    And the following "AcmeApiBundle:Article" entities exist:
      | id | title  |
      | 1  | Foo    |
      | 2  | Bar    |
      | 3  | Foobar |

  Scenario: Get articles
    When I GET to "/articles/"
    Then the response status code should be 200
     And the api result should be ok
     And the api result should contain 3 items

  Scenario: Get an article
    When I GET to "/articles/2"
    Then the response status code should be 200
     And the api result should be ok
     And the api result should contain key "title"
     And the api result key "title" should equal "Bar"

  Scenario: Get a non-existing article
    When I GET to "/articles/40"
    Then the response status code should be 404
     And the api result should be not ok

  Scenario: Insert a new article
    When I POST to "/articles/" with:
      """
        {
          "title": "New article"
        }
      """
    Then the response status code should be 201
     And the api result should be ok
     And the api result should contain key "title"
     And the api result key "title" should equal "New article"

  Scenario: Update an existing article
    When I PUT to "/articles/2" with:
      """
        {
          "title": "Baz"
        }
      """
    Then the response status code should be 200
     And the api result should be ok
     And the api result should contain key "title"
     And the api result key "title" should equal "Baz"

  Scenario: Delete an existing article
    When I DELETE to "/articles/2"
    Then the response status code should be 200
     And the api result should be ok
     And the entity "AcmeApiBundle:Article" with id 2 does not exist
```
