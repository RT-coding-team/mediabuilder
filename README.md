# MM Content Builder

The MM Content Builder is a tool to build the content used by the [MM Interface](https://github.com/RT-coding-team/mediainterface).  It is built on the [Bolt CMS](https://boltcms.io/).  There is no frontend to this code base, so all users will need a login to use it.

## Configuration

In order to set the supported languages for the content builder, you need to edit the following configurations:

- In `config/bolt/config.yml`, add all relating languages in the exporter/supported_languages field.
- In `config/bolt/contenttypes.yml`, add the two letter language code for each field that has a locales option.
- In `config/services.yml`, verify the language is in the array parameters/app_locales
