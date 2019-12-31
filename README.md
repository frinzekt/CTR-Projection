# Cost Time Resource (CTR) Projection
<img src="https://user-images.githubusercontent.com/44391389/71617827-d0e7f380-2bf7-11ea-9f5a-99a0a847a528.png" alt="CTR-projection" width="1000px" />


## Description

The success of delivery of a project is dependent on the proper allocation of resources in different subjobs and tasks that are assigned to a project. Resources such as finance is limited by the budget, therefore allocations has to be higher for higher priority tasks. Another important resource is time, where specific portions of the project has to meet deadlines in order to maintain a positive stakeholder relationship.

Resource management requires data to be analyzed such as:
 1. Current Budget
 1. Original Schedule / Forecasted Expenditure
 1. Amount Invoiced
 1. Amount Paid
 1. Amount Spent
 1. Project Value
 
## Current System
Currently, data is manually fed to an excel template to be able to visualize the required projection. This takes a lot of time especially for large projects and is redundant when there are data sources that are already in place. In this case, there is a live system where data resides to be able to get from.

## Task
To create a web application that integrates to the current system in place that is able to utilize the data sources in the live system in order to project CTR visualization


## Other Documentation

#### Library and Dependency Used
- Plotly - for graphing/chart display
- Handsontable - for tabular display
- Jquery - event handling and interactions
- Numjs - array and group of data operations
- Boostrap - design and layout

#### Software Architecture
**Frontend**
- PHP - basic templating directly from database
- HTML, CSS
- Javascript - connection of the backend and manipulates UI

**Backend**
- PHP - interaction with MySQL server and AJAX calls
