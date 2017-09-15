// Globale variables

// Siden URL
var options = "";

// Brukervariabler
var user = {type: 0, id: 0, name: "NONAME"};

// Functions to run after DOM is ready
$(document).ready(function(){
    // Initsiering

    // Vi lagrer url-en i options-stringen slik at du vi kan lese den senere
    options = parseUrl(window.location.href);
        // Regex for å finne ut om ?debug-kommandoen er inkludert i URL
        if (/debug/i.test(options)) {
            $(".debug").show();
        }
    // Oppdater skjermen så vi får med alle ajax-kall
    redraw();

    // Database queries

    // Lager et html-element med teknikere som hører til en konsert
    function getListOfTechnicians(bruker, concertID) {
        // TODO database-call: (userid,concertID)
        let l = ["Jens", "Nils", "Truls"];

        // Vi bygger et HTML-element
        let listContainer = $("<ul></ul>").addClass("technicianlist");
        for (i in l) {
            let listPoint = $("<li></li>").text( l[i]);
            listContainer.append(listPoint);
        }
        return listContainer;
    }

    // Lager et html-element med konserter
    function getListOfConcertes(bruker) {
        // TODO: database-call: (userid)
        let l = []
        if (bruker.type == 1) {
            l = ["Konsert 1", "Konsert 2", "Konsert 3", "Konsert 4", "Konsert 5"];
        } else if (bruker.type == 2) {
            l = ["Konsert 1", "Konsert 2"];
        }
        // Get from database
        let concertID = 0;

        // Vi bygger et element
        let listContainer = $("<ul></ul>").addClass("concertlist");
        for (i in l) {
            let listPoint = $("<li></li>");
            let concertInfo = $("<span></span>").text(l[i]);
            let concertButton = $("<button></button>").addClass("concert_button").attr("id",concertID).text("Mer info");
            listPoint.append(concertInfo, concertButton);
            listContainer.append(listPoint);
        }
        return listContainer;
    }

    // Lager et html-element med informasjon om en konsert
    function getConcertInfo(bruker, concertID) {
        // TODO: database-call(userid, concertID)

        // Vi bygger et HTML-element
        let container = $("<div></div>").text("informasjon om konsert med ID:"+concertID);
        if (bruker.type = 1) {
            container.append("<br> Teknikere", getListOfTechnicians(bruker, concertID));
        }
        return container;
    }
    

    // FUNCTIONS

    // Tegner siden på nytt etter brukertype
    function redraw() {
        switch(user.type) {
            case 0: // Ikke pålogget
                $.ajax({url: "no_user.html",dataType: 'html', success: function(result){
                    $("#root").html(result);
                }});
                break;
            case 1: // Bruker er arrangør
                $.ajax({url: "arrang.html",dataType: 'html', success: function(result){
                        $("#root").html(result);
                }});
                break;
            case 2: // Bruker er teknikker
                $.ajax({url: "tekni.html",dataType: 'html', success: function(result){
                    $("#root").html(result);
                }});
                break;
            default:
                $("#root").html("<p>Error: invalid usertype "+user.type+"</p>");
        }
        console.log("Pagestate:"+user.type);
    }

    // Finner sidens URL-addresse
    function parseUrl( url ) {
        var a = document.createElement('a');
        a.href = url;
        return a;
    } 

    // Henter informasjon fra bruker- og passord-felt og prøver å logge inn
    function logon() {
        user.name = $("#username_field").val();
        console.log("Username "+user.name);
        // TODO
        if (user.name.charAt(0) == 'a') {
            user.type = 1;
        } else if (user.name.charAt(0) == 'b') {
            user.type = 2;
        }
        redraw();
    }

    // Logger ut, for nå så laster den bare siden på nytt
    function logout() {
        console.log("Logout");
        location.reload();
    }

    // EVENTS

    // Debug-knapper
    $(".debug_button").click(function() {
        user.type = parseInt(this.id);
        redraw();
    });

    // Fang 'enter'-trykk fra brukernavn-feltet
    $('body').on('keyup', "#username_field", function (e) {
        if (e.keyCode === 13) {
           logon();
        }
    });

    // Fang 'enter'trykk fra passord-feltet
    $('body').on('keyup', "#password_field", function (e) {
        if (e.keyCode === 13) {
           logon();
        }
    });

    // Fant trykk på påloggingsknappen
    $('body').on('click', ".login_button", function () {
        logon();
    });

    // Fang trykk på avloggingsknappen
    $('body').on('click', ".logout_button", function () {
        logout();
    });

    // Fang trykk på knapp for mer informasjon om konsert
    $('body').on('click', ".concert_button", function () {
        let concertID = parseInt(this.id);
        console.log("Get concert info");
        $(this).after(getConcertInfo(user,concertID));
        // Denne funksjonen skjuler bare knappen etter å ha blitt kallet, alternativ: bruk $(...).toggle();
        $(this).hide();
    });

    // VIKTIG FUNKSJON: Kan injsere innhold i DOM-treet etter ajax-oppdatering.
    $(document).ajaxComplete(function() {
        $('#username').html(user.name);
        $('#listofconcerts').append(getListOfConcertes(user));
    });

    

});