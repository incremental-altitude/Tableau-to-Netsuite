
# Tableau to Netsuite Integration/Sync

This is a program designed to run a script that integrates a Tabluea Report View Line data into Netsuite, via Netsuite's Php Toolkit and SOAP API
This requires 2 Vendor Dependencies - Netsuite PHP, and SimpleXLSX.



## Requirements
You will need Tableau Login Credentials to generate a Tableau Authentication Token. 
You will also need to setup a Netsuite Integration in Netsuite with decent concurrency governance. (min 5 recommended). TBA Access Token should also be used for Netsuite Integration Configuration.

If continuing to use PHP, you will require Composer, and the following dependencies.

https://github.com/netsuitephp/netsuite-php

https://github.com/shuchkin/simplexlsx


## How it works.
This application initiates a Tableau GET View request, that returns a XLSX export of the view. You can set the target view by setting the tableau view internal id in the credentials file. You can also set the Tableau site Id in the credentials file.

You will also need to set all Netsuite Consumer Key/Secrets and Token Key/Secrets in the credentials file.

When a view file is returned as a XLSX file from Tableau, the data is stored to the application's data.xlsx file. This file is then parsed by the simplexlsx dependency and retuns an array of Row by Row Values from the View.

The Key Value Pairs for these rows is the starting point for all logical functionality and customization of the application script.

2 functions are declared in the script.
updateWorkOrder
searchWorkOrder

These functions are called to sync specific Tableau Key-Value Paired Data into thier Netsuite WorkOrder or other Record Counterparts.  (the Sync)

The key identifier at this time of release is Tableau's Custom Reference Field. This should equate to the Netsuite Work Order's Internal Id field and 1 to 1 relationship.

If this value is missing, the searchWorkOrder function will search Netsuite for a record based on the Tableau Run Id field value. This equates to the Netsuite tranid field, and usually follows a naming convention of WO#####. The searchWorkOrder function returns the Work Order's Internal ID, which in turn can used to run the updateWorkOrder function successfully.

The daterange of data synced should be set in the Tableau View, and this script scheduled via a Web Server Cron Job or Scheduled Task for automated and continued sync processing. 

Note: There has been significant Array Processing applied to the Tableau Rows Data in order to fit Cosmetic Solutions Customization of the Sync. Details can be found in the Tableau Data-Manipulation and Organization Section.

## Tableau Data Manipulation and Organizing
Details Coming Soon.
## API Reference

#### Get all items

```https
  GET /api/items
  https://tableau.rz-ops.com/api/3.4/auth/signin

```

| Parameter | Type     | Description                |
| :-------- | :------- | :------------------------- |
| `api_key` | `string` | **Required**. Your API key |

#### Get item

```http
  GET /api/items/${id}
```https://tableau.rz-ops.com/api/3.9/sites/

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `id`      | `string` | **Required**. Id of item to fetch |

#### add(num1, num2)

Takes two numbers and returns the sum.


## Acknowledgements




## Authors

- [@nastystyles](https://github.com/nastystyles)
- Jarrod Dinwoodie 2024 - Cosmetic Solutions LLC in Partnership with Binary Bear LLC
