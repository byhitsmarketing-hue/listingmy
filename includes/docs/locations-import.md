# Supported format for manual import

### Serialized format (preferred method)
In **Address:** field add locations in serialized format, the same format that is generated when you export listings using WP All Export

The serialized string should contain the address, latitude, and longitude for each location.

Example 1: `a:2:{i:0;s:75:"17 Elim Way, Canning Town, London, E13 0EH, United Kingdom,51.52454,0.01526";i:1;s:56:"7 Stone Buildings, London WC2A 3SZ, UK,51.51693,-0.11373";}`

Example 2: `a:1:{i:0;s:83:"27 Old Gloucester Street, Holborn, London, WC1N 3AF, United Kingdom,51.52083,-0.122";}`

### Notes for Serialized Format:

- Ensure that the length value (`s:XX`) accurately reflects the character count of the location string.
- Each location entry (`i:X`) must be sequentially indexed starting from 0.

### Unserialized
For single-location listings where each component of the address is provided in separate columns, map each to the corresponding field as shown below:

- 'address_column' Map to **Address** 
- 'latitude_column' Map to **Latitude** 
- 'longitude_column' Map to **Longitude** 
