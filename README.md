# MM Content Builder

The MM Content Builder is a tool to build the content used by the [MM Interface](https://github.com/RT-coding-team/mediainterface).  It is built on the [Bolt CMS](https://boltcms.io/).  

Intro Video: https://www.loom.com/share/a5b937084aa442b4bfb1b9a9bd552688

## Installation

There is a full [Well / Connectbox Server](https://github.com/RT-coding-team/chathost) available to download with instructions.  However, advanced users may find utility in a solo installation using docker-compose file in this repo with nginx configured for PHP or other [Bolt CMS installation methods](https://docs.boltcms.io/5.0/installation/installation).

## Configuration

Create an environment file from .env.example and replace fields such as mysql password that provides user credentials.  

In order to set the supported languages for the content builder, you need to edit the following configurations:

- In `config/bolt/exporter.yml`, update the site_url to the full URL of the site and add all relating languages in the exporter/supported_languages field.
- In `config/bolt/contenttypes.yml`, add the two letter language code for each field that has a locales option.
- In `config/services.yml`, verify the language is in the array parameters/app_locales

## Usage

The docker installation will create an `admin` user account using the Master Password in the .env file.  You can log in and create additional users.

## Usage Videos

- [Logging In]()
- [Introduction To Data Types]()

## Exporter API

We provide a simple endpoint on the Exporter.  Here is a list of available endpoints.

**GET**

/exporter/api/files.json

Retrieve a list of archives that are currently available.  This does not require authentication.

**Response**

```
[
    {
        "date": "Sep 16, 2021 11:15 PM",
        "filename": "gospel_09-16-2021-23-15.zip",
        "filepath": "http://localhost:8080/files/exports/gospel_09-16-2021-23-15.zip",
        "package": "Gospel",
        "timestamp": 1631834100
    }
,    {
        "date": "Sep 16, 2021 10:46 PM",
        "filename": "bible_09-16-2021-22-46.zip",
        "filepath": "http://localhost:8080/files/exports/bible_09-16-2021-22-46.zip",
        "package": "Bible",
        "timestamp": 1631832360
    }
]
```

## Libraries

Here is a list of libraries being used in the backend to make development easier:

- [Bootstrap 4](https://getbootstrap.com/docs/4.1/)
- [Bootbox](http://bootboxjs.com/) doesn't work because JQuery is not loaded yet.  Instead I use [MicroModal](https://micromodal.vercel.app/).
- [JQuery](https://api.jquery.com/)
