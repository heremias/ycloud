# Welcome to Grid Content smoke tests documentation

In order for Open Y Grid Content being tested in a short timeframe, please follow steps below

# Paragraph: Grid Content								

## Adding paragraph to a page

### User

Administrator

### Steps

1. Login as Admin 
2. Go to Content page -> Add Content
3. Create a new landing page
4. In the Content area verify you can add a paragraph called ""Grid content""
5. Verify paragraph has only one required field called ""Style""
6. Verify the paragraph provides the following fields to configure it:
- Style  - to choose number of blocks per row 
- Content with the following fields:
  - Headline 
  - Icon (does not work in Carnation)
  - Icon Class (does not work in Carnation)
  - Description
  - Link (URL + Link text + attributes)
- Add Grid columns button. 
7. Verfiy there are no errors and page created successfully using paragraph
8. Verify there are no errors if change style 2 per row, 3 per row, 4 per row 
8. Verify you can edit the page, edit paragraph, without any issues

### Expected Results

1. Login as Admin 
2. Go to Content page -> Add Content
3. Create a new landing page
4. In the Content area verify you can add a paragraph called ""Grid content""
5. Verify paragraph has only one required field called ""Style""
6. Verify the paragraph provides the following fields to configure it:
- Style  - to choose number of blocks per row 
- Content with the following fields:
  - Headline 
  - Icon (does not work in Carnation)
  - Icon Class (does not work in Carnation)
  - Description
  - Link (URL + Link text + attributes)
- Add Grid columns button. 
7. Verfiy there are no errors and page created successfully using paragraph
8. Verify there are no errors if change style 2 per row, 3 per row, 4 per row 
8. Verify you can edit the page, edit paragraph, without any issues

## Check DEMO page with grid content

### User

Administrator

### Steps

1. Login as Admin 
2. Go to Content page
3. Check the following pages, they should contain grid content in the Content area:
- Homepage /
- Downtown YMCA /locations/downtown-ymca
- East YMCA /locations/east-ymca
- South YMCA /locations/south-ymca
- West YMCA /locations/west-ymca

### Expected Results

1. The following demo pages contain grid content in the Content area:
- Homepage /
- Downtown YMCA /locations/downtown-ymca
- East YMCA /locations/east-ymca
- South YMCA /locations/south-ymca
- West YMCA /locations/west-ymca

## Check paragraph functionality

### User

Administrator / Anonymous

### Steps

1. Go to existing pages with a grid content (see test case above) or create a new landing page with a grid content added (see test case above)
2. Verify page with a grid content paragraph in a style 2 items per row looks good
3. Verify page with a grid content paragraph in a style 3 items per row looks good
4. Verify page with a grid content paragraph in a style 4 items per row looks good

### Expected Results

1. Grid content paragraph looks good for all style options
2. There are no errors while working with this paragraph
