# Matomo TrackingCLI Plugin

## Description
Import tracking data to Matomo via CLI and stdin.

## Interface

```bash
./console help trackingcli:import
```

```
Usage:
 trackingcli:import [-s|--idsite="..."] [-c|--columns="..."] [-d|--delimeter[="..."]] [-z|--batchsize[="..."]] [inputfile]

Arguments:
 inputfile             Path to input file or '-' for stdin (default: "-")

Options:
 --idsite (-s)         Matomo site ID
 --columns (-c)        Columns map
                           Format: matomoApiArgumentName1|matomoApiArgumentName2|...
                           Example: url|action_name|ua
                           See https://developer.matomo.org/api-reference/tracking-api for details
 --delimeter (-d)      Columns delimeter
                           Format: s - character, \digits - the character with the given decimal code
                           Example: |
                           Example: \0 (default: "\\29")
 --batchsize (-z)      Batch size when importing (default: 100)
 --help (-h)           Display this help message
 --quiet (-q)          Do not output any message
 --verbose (-v|vv|vvv) Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
 --version (-V)        Display this application version
 --ansi                Force ANSI output
 --no-ansi             Disable ANSI output
```

## Example

data.txt:
```
2019-04-20 05:44:00|Page title 1|http://test.com#1|Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.146 Safari/537.36
2019-04-20 05:44:00|Page title 2|http://test.com/foo/bar|Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.146 Safari/537.36
2019-04-20 05:44:00|Page title 3|http://test.com/test3#3|Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.146 Safari/537.36
```

import:
```bash
./console -vvv trackingcli:import -s1 -c'cdt|action_name|url|ua' -d'|' -z2 < data.txt
Success
Requests imported: 3
Memory peak usage: 23068672
```
