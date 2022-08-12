# Welcome to Banner smoke tests documentation

In order for Open Y Banner being tested in a short timeframe, please follow steps below

# Paragraph: Banner								

## Adding paragraph to a page

### User

Administrator

### Steps

1. Login as Admin 
2. Go to Content page -> Add Content
3. Create a new landing page
4. In the Header area verify you can add a paragraph called ""Banner""
5. Verify Banner paragraph has the following fields
- Headline (should be required)
- Color (should be required)
- Description 
- Image 
- Link (URL, link text and attributes)
6. Verfiy there are no errors and page created successfully using paragraph
7. Verify you can edit the page, edit paragraph, without any issues

### Expected Results

1. There is paragraph called ""Banner""
2. Banner paragraph contains of the following fields: 
- Headline (required)
- Color (required)
- Description 
- Image 
- Link (URL, link text and attributes)
3. Background color for the banner works correctly 
4. Image as a background for the banner works correctly

## Check DEMO page with microsite menu

### User

Administrator

### Steps

1. Login as Admin 
2. Go to Content page
3. Check the following pages, they should contain banner in the Header area:
- Homepage /
- Swimming Lessons at the YMCA /swimlessons

### Expected Results

1. The following demo pages contain banner in the Header area:
- Homepage /
- Swimming Lessons at the YMCA /swimlessons

## Check paragraph functionality

### User

Administrator / Anonymous

### Steps

1. Go to existing pages with a banner (see test case above) or create a new landing page with a banner added (see test case above)
2. Verify banner looks good on the page 
3. Verify it goes under the main menu and creates background for the main menu (Carnation specific)
4. Verify banner looks good with/without image/description/link.

### Expected Results

1. Banner paragraph looks good
2. There are no errors while working with this paragraph
