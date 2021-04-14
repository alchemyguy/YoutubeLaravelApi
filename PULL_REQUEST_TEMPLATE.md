<!--- Provide a general summary of your changes in the Title above -->

## Description

The function **subscriptionByChannelId** didn't work properly. It was getting always the first 50 results and the user could not get more or less. The parameter 'maxResults' didn't do anything.

I reactivated the function **parseSubscriptions** and fixed it to get the desired amount of results instead of all of them.

To avoid confuision, the parameters is passed now as **totalResults** instead of **maxResults** since the last is the Google Api's parameter for each page, which can only be set up to 50

The Read Me was updated

## Motivation and context

User was not able to get results off the first page

## How has this been tested?

I listed my own channel subscriptions

## Screenshots (if appropriate)

## Types of changes

- [ ] Bug fix (non-breaking change which fixes an issue)
- [X] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to change)

## Checklist:

- [X] I have read the **[CONTRIBUTING](CONTRIBUTING.md)** document.
- [X] My pull request addresses exactly one patch/feature.
- [X] I have created a branch for this patch/feature.
- [X] Each individual commit in the pull request is meaningful.
- [X] I have added tests to cover my changes.
- [X] If my change requires a change to the documentation, I have updated it accordingly.