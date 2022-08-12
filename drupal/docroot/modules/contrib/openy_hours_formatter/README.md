# openy_hours_formatter
Hours Formatter Widgets for Open Y Distribution

![image](https://user-images.githubusercontent.com/563412/123424798-ffb31000-d5c9-11eb-8225-a8a36d05369e.png)

## Open Y Hours Metatag Tokens

This module provides three tokens to be used in conjunction with [Schema.org Metatag](https://www.drupal.org/project/schema_metatag) to enable translation of the Open Y "Branch Hours" field to [Schema.org OpeningHoursSpecification](https://schema.org/openingHoursSpecification) objects.

To use them:

- Require and enable the Schema Metatag module and any Schema Type submodules that you plan to use. We recommend that Branches use the "Schema.org Organization" module.
- Configure Branch nodes to use the "LocalBusiness" Organizatoin type, then configure the rest of your fields.
- In the "OpeningHoursSpecification" section, set the following:
  - @type - OpeningHoursSpecification
  - Pivot - Pivot
  - dayOfWeek - [openy_hours:day_of_week]
  - opens - [openy_hours:opens]
  - closes - [openy_hours:closes]
- Save and clear caches. You should see the proper metadata populating the `<script type="application/ld+json">` section of the `<head>` on Branch pages.

