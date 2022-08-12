# Welcome to Location Finder smoke tests documentation

In order for Open Y Location Finder being tested in a short timeframe, please follow steps below

# Paragraph: Location finder filters + Paragraph: Location finder

## Adding paragraphs to a page

### User

Administrator

### Steps

1. Login as Admin 
2. Go to Content page -> Add Content
3. Create a new landing page using one column layout 
4. In the header area verify you can add a paragraph called ""Location finder filters""
5. In the Content area verify you can add a paragraph called ""Location finder""
6. Save the page 
7. Verfiy there are no errors and page created successfully using these paragraphs

### Expected Results

1. There is a pagraph called ""Location finder filters"" that can be added to a landing page 
2. There is a pagraph called ""Location finder"" that can be added to a landing page 
3. Added paragraphs works well after saving the page 

## Check DEMO page with paragraphs

### User

Administrator

### Steps

1. Login as Admin 
2. Go to Content page
3. Search by title ""Locations""
4. Verify there is a landing page with such a title
5. Open it 
6. Verify this page contains the following elements: 
 - map with pins 
 - filters (search by zip or street, distance and checkboxes with types of locations (YMCA, Camps, Facilities))
7. Verify there is a listing with locations groupped by type of location. 
8. Verify results of the listing are correct comparing created locations on the Content page. 

### Expected Results

1. There is a landing page called ""Locations""
2. Page contains important elements, such as map with pins, filters and locations listing below
3. Listing contains correct locations and nothing missing there. 

## Check paragraphs on the page

### User

Anonymous

### Steps

1. Go to existing ""Locations"" page (see test case above) or create a new landing page with paragraphs added (see test case above)
2. Verify there is a map with pins of locations 
3. Verify there are filters right below the map
4. Verify there is a listing of locations on the page


### Expected Results

1. There is a map with pins of locations 
2. There are filters right below the map 
3. There is a listing of locations

## Check location finder filters paragraph functionality

### User

Anonymous

### Steps

1. Go to existing ""Locations"" page (see test case above) or create a new landing page with paragraphs added (see test case above)
2. Verify some locations and check pins are set correctly according to location address
3. Verify that popup appears with location details (similar data as in the listing) when you click on the pin. 
4. Verify location title is clickable and direct user to the location page 
5. Verify phone number is clickable 
6. Verify Directions link direct user to the Google Map with predefined address 
7. Verify Branch hours (for branches) displayed correctly
8. Verify the map automatically changes zoom when filters applied (by type of location)
9. Verify filter by zip code works correctly and limits the number of pins on the map + limits the number of locations in the listing . Zip code for example: 77002
10. Verify filter by distance filters locations correctly and limits the number of pins on the map + limits the number of locations in the listing 
11. Verify filter by type of locations works correctly and limits the number of pins on the map + limits the number of locations in the listing


### Expected Results

1. Pins on the map are set correctly according to location address
2. Popup appears with location details (similar data as in the listing) when user clicks on the pin
3. Location title is clickable and direct user to the location page 
4. Phone number is clickable 
5. Directions link direct user to the Google Map with predefined address 
6. Branch hours (for branches) displayed according to configuration
7. Mmap automatically changes zoom when filter by type of location applied
9. Filter by zip code works correctly
10. Filter by distance filters locations correctly 
11. Filter by type of locations works correctly
12. All filters limits the number of pins on the map + limits the number of locations in the listing

## Check location finder paragraph functionality

### User

Anonymous

### Steps

1. Go to existing ""Locations"" page (see test case above) or create a new landing page with paragraphs added (see test case above)
2. Verify some locations and check the data displayed in the listing is corerct, according to location configuration
3. Verify all locations groupped by location type (see filters)
3. Verify location title is clickable and direct user to the location page 
4. Verify phone number is clickable 
5. Verify Directions link direct user to the Google Map with predefined address 
6. Verify Branch hours (for branches) displayed correctly
7. Verify each location has a button that directs to the location page



### Expected Results

1. Data displayed in the listing is corerct, according to location configuration
2. All locations groupped by location type (see filters)
3. Location title is clickable and direct user to the location page 
4. Phone number is clickable 
5. Directions link direct user to the Google Map with predefined address 
6. Branch hours (for branches) displayed correctly
7. Each location has a button that directs to the location page
