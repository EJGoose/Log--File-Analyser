# Log File Analyser
-----------
## Project Description
The log file analyser takes a log file and allows users to apply filters, and see a breakdown of the details within the file. If no file is submitted the analyser uses a template file, otherwise, the user-uploaded file is saved locally. This file is then reviewed by the script and broken into its parts. If the user has applied a filter a filtered version of the log file will be displayed at the bottom of the page. Otherwise the user will receive a log report and the full file will be displayed.

### This Project Explores
- File handling with PHP, including saving files, and handling errors during the file upload process. 
- Regular expressions in various contexts including using `preg_replace`, `preg_match`and `preg_quote` functions.
- Using PHP to render HTML and CSS dynamically.
- Managing user input safely.

### Notes
- To run this file locally you will require a local server, during development I used XAMPP to run an Apache server.
