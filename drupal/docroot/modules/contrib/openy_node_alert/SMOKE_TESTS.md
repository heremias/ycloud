# Welcome to Open Y Alert App smoke tests documentation

In order for Alert App being tested in a short timeframe, please follow steps below

## Editing Alerts

### User

Administrator

### Steps

1. Go to Content -> Add Content -> Alert (/node/add/alert)
2. Verify you can see the form for creating alerts 
3. Verify the following fields are required: 
 - Title (plain text)
 - Desscription (text editor)
 - Background color (dropdown)
 - Text color (dropdown)
 - Placement 
4. Verify the following fields are optional: 
 - Icon color (dropdown)
 - Link (renders as a button)
 - Visibility Pages -> Pages 
 - Location (dropdown)
5. Fill the form with some data 
6. Verify alert created and placed in the correct area of the website (header or footer)
7. Verify alert look good and correct, according to specified configuration
8. Edit Alert and modify some alert settings (background color, link, text colorm etc)
9. Verify changes applied correctly.  

### Expected Results

1. Administrator or any user with permissions to create/edit alerts has access to the form 
2. Form provide some required fields and some optional
3. Form provides the possiblity configure where alerts should be placed. 
4. Alert can be placed in both header and footer 
5. Alert can be different (look & feel) based on the setings (colors)"

## Alerts visibiliy configuration

### User

Administrator

### Steps

1. Specify in the alert some pages where alert should be visible (using the option ""Show for the listed pages""). 
2. Verify alert is visible only on the specified pages. 
3. Verify visibility of the alert after changing option to ""Hide for the listed pages""
4. Verify location dropdown is not empty, it should contain the list of Branches and Camps"

### Expected results

1. Alert can be hidden from specified pages or displayed only on specified pages. There are no limitations on the number of pages. 
2. Alert can be displayed on Landing pages associated with Branch/Camp after specifiying parent Branch/Camp. "

## Alerts rendering and work on the front-end

### User

Anonymous

### Steps

1. Verify you see alerts below the banner (header placement) and above the footer (footer placement). 
2. Click on the left and right arrows, check that alerts changing
3. Verify each alert contains icon, title, description, button(optional) and cross icon
4. Verify alerts have different background colors. Icon color also is changable. 
5. Verify, if you click on the cross icon alert disappers. Verify after reloading the page it is not visible anymore.
6. Verify, all clickable elements have hover and focus styles."

### Expected results

1. Alerts are displaying 
2. Alerts can be placed in header and in footer
3. Alerts contain required attrributes such as icon, title, description, bacground color, cross icon.
4. Alerts may contain optional attrributes such as call to action button. 
5. Alerts can be hidden and not visible anymore after reloading the page
6. All clickable elements have hover and focus styles and look good"
