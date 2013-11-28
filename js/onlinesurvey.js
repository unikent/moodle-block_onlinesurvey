YUI().use("node", "io", "dump", "json-parse", function(Y) {
    Y.log("Started");
    var survey = Y.one("#onlinesurvey-text");
    var surveyfooter = Y.one("#onlinesurvey-footer");
    survey.setHTML("Requesting surveys...");
    
    var callback = {

        timeout : 3000,
        method:"GET",
        data:{sesskey:M.cfg.sesskey},

        on : {
            success : function (x,o) {
                Y.log("RAW JSON DATA: " + o.responseText);

                // Process the JSON data returned from the server
                try {
                    data = Y.JSON.parse(o.responseText);
                }
                catch (e) {
                    survey.setHTML("Unable to obtain surveys.");
                    return;
                }

                Y.log("PARSED DATA: " + Y.Lang.dump(data));
                if (data.error) {
                    survey.setHTML("Unable to obtain surveys..");
                } else {
                    survey.setHTML(data.text);
                    surveyfooter.setHTML(data.footer);
                }
            },

            failure : function (x,o) {
                survey.setHTML("Unable to obtain surveys...");
            }
        }
    }
    
    Y.io("blocks/onlinesurvey/ajax.php", callback);
});