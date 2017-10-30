/*

Javascript-side som inneholder funksjoner og variabler som blir brukt av flere (eller alle) andre script, må lastes først

*/





// Brukervariabler
var user = {type: 0, id: 0, name: "NONAME"};

// Bestemmer om siden skal vise utviklerinformasjon
var debug_mode = false;

// Teller hvor mange tilbud som venter på brukerrespons
var ventende_tilbud = 0;

// Lager et html-element med informasjon om en konsert
function getConcertInfo(bruker, concert) {

    // Vi bygger et HTML-element
    let container = $("<div></div>").text(" ").addClass("concertInfo").attr('id', 'cid' + concert.kid);
    console.log(concert);
    if (bruker.type===1) {
        let maindiv = $("<div></div>").addClass("behov");
        let tb = $("<h3></h3>").text("Tekniske Behov:");
        maindiv.append(tb);
        getTechnicalNeedsByKid(bruker.id,concert.kid, concert.navn, concert.dato, maindiv);
        getListOfTechnicians(bruker, concert);
        container.append(maindiv);
    } else if (bruker.type===2) {
        let tb = $("<h3></h3>").text("Tekniske Behov:");
        container.append(tb);
        getTechnicalNeedsByKid(bruker.id, concert.kid, concert.navn, concert.dato, '#cid'+concert.kid);
    } else if (bruker.type == 5) {
        let concertReportDiv = $("<div></div>").addClass("EcReport");
        let cost_report = $("<h3></h3>").text("Økonomisk rapport:");
        concertReportDiv.append(cost_report);
        BSbuildConcertReport(concert.kid, concert.navn, concertReportDiv);
        container.append(concertReportDiv);
    }
    container.hide();
    return container;
}

// Lager et html-element med teknikere som hører til en konsert (ARRABGØR)
function getListOfTechnicians(bruker, concert) {

    $.ajax({ url: '/database.php?method=getListOfTechs',
        data: {username: user.name, usertype: user.type, concertid: concert.kid},
        type: 'post',
        success: function(output) {
            l = safeJsonParse(output); //gjør en try-catch sjekk.

            // Vi bygger et HTML-element
            let listContainer = $("<div></div>").addClass("technicianlist");
            listContainer.append("<h3>Teknikere: </h3>");

            for (i in l) {
                let listPoint = $("<span></span><br>").text(l[i].fornavn +' '+ l[i].etternavn);
                listContainer.append(listPoint);
            }

            listContainer.append(listContainer);
            $('#cid'+concert.kid).append(listContainer);


        },
        error: function(xmlhttprequest, textstatus, message) {
            if(textstatus==="timeout") {
                alert("Timeout feil, kan ikke koble til databasen");
            } else {
                console.log("Error: "+message);
            }
        }
    });

}

//Try catch funksjon for json-parse
function safeJsonParse(output) {
    try{
        l = jQuery.parseJSON(output);
    }
    catch(err){
        console.log(err);
        console.log(output);
        $("#root").after(output);
    }
    return l;
}

/*
Hvordan bruke denne:

html_id skal være en id eller klasse f.eks #root som listen settes inn i.
list skal være en liste over objekter
formatingunction skal være en funksjon som tar ett objekt fra list og gjør det til html
det forventes at formateringsunfksjonen skal være på formen:

    function(html_id, element) {
        let container = $("<span></span>");

        ...

        $("#"+html_id).append(container);
    }

Denne funksjonen er async-sikker og har som hovedoppgave å redusere boilerplate-kode i forbindelse med listebygging i sammenheng med ajax
*/
function injectList(html_id, list, formatingfunction) {
    if (list.length === 0) {
        return;
    }
    let listContainer = $("<ul></ul>");
    let child_id = [];
    for (let i = 0; i<list.length; i++) {
        child_id.push(html_id+"_"+i+"_");
        let listElement = $("<li></li>").attr("id",child_id[i]);
        listContainer.append(listElement);
    }

    $("#"+html_id).append(listContainer);

    for (let i = 0; i<list.length; i++) {
        formatingfunction(child_id[i],list[i]);
    }
}



// Bygger en korrekt liste av scener
function buildListOfConcerts(bruker,list) {
    let listContainer = $("<ul></ul>").addClass("concertlist");
    for (let i in list) {
        let listPoint = $("<li></li>");
        let concertInfo = $("<div></div>").addClass("button_text").text(' ' + list[i].navn +' | ' + list[i].dato +  ' | ' + list[i].start_tid + " - " + list[i].slutt_tid);
        let concertButton = $("<button></button>").addClass("concert_button").text("Mer info");
        listPoint.append(concertInfo, concertButton, getConcertInfo(bruker, list[i]));
        listContainer.append(listPoint);
    }
    return listContainer;
}

// Bygger HTML for tekniske behov
function getTechnicalNeedsByKid(bruker_id,kid, kname, dato, container) {
    let l = [];

    $.ajax({ url: '/database.php?method=getListOfTechnicalNeeds',
        data: {concertid: kid},
        type: 'post',
        success: function(output) {
            l = safeJsonParse(output); //gjør en try-catch sjekk.

            // Vi bygger et HTML-element
            let kid = $("<h3></h3").text('Konsert : ' + kname + " - " + dato).addClass("tb_overskrift");
            let listContainer = $("<div></div>").addClass("behov");
            listContainer.append(kid);
            if (l.length === 0) {
                listContainer.append('Ingen tekniske behov meldt enda.')
            }
            listContainer.append('<br>');
            for (i in l) {
                let tittel = $("<span></span>").text('Tittel: ').css('font-weight', 'bold');
                let tittel2 = $("<span></span><br>").text(l[i].tittel);
                let behov2 = $("<span></span><br>").text(l[i].behov);
                let behov = $("<span></span>").text('Beskrivelse: ').css('font-weight', 'bold');
                listContainer.append(tittel, tittel2, behov, behov2)
                if (bruker_id === 3) {
                    let delete_button = $("<button>Slett</button>").addClass("delete_technical_need").addClass("delete_button").val(l[i].tbid);
                    listContainer.append(delete_button);
                }
                listContainer.append('<br>');

            }
            $(container).append(listContainer);

        },
        error: function(xmlhttprequest, textstatus, message) {
            if(textstatus==="timeout") {
                alert("Timeout feil, kan ikke koble til databasen");
            } else {
                console.log("Error: "+message);
            }
        }
    });
}


// Bygger en liste over alle tilbud mottat av brukeren
function injectOffers(bruker) {
    $.ajax({ url: '/database.php?method=getOffers',
        data: {uid:bruker.id, brukertype:bruker.type},
        type: 'post',
        success: function(output) {
            l = safeJsonParse(output);

            // Registrerer y-posisjon på skjermen, vi bruker dette posisjonen for å forhindre at skjermen scrollen hvis et tilbud endres eller slettes
            ventende_tilbud = 0;
            let yoffset = window.pageYOffset;
            $("#manager_tilbud").empty();

            injectList("manager_tilbud",l,function(html_id,element){

                injectList(html_id,element,function(html_id,element){



                    // Overskrift
                    let overskrift = $("<h2></h2>");
                    let band_navn = $("<span></span>").text("Band: "+element.band_navn);
                    let scene_navn = $("<span></span>").text("Scene: "+element.scene_navn);
                    overskrift.append(band_navn," på ", scene_navn);

                    // Sender
                    //let sender = $("<p></p>");
                    let sender_navn = $("<span></span><br>").text("Sender: "+element.sender_fornavn +" "+element.sender_etternavn);
                    //sender.append(sender_navn);


                    // Tidspunkt
                    //let tidsinfo = $("<p></p>");
                    let dato = $("<span></span><br>").text("Dato: "+element.dato);
                    let start_tid = $("<span></span><br>").text("Start: "+element.start_tid);
                    let slutt_tid = $("<span></span><br>").text("Slutt: "+element.slutt_tid);
                    //tidsinfo.append(dato, start_tid, slutt_tid);


                    // Pris
                    //let prisinfo = $("<p></p>")
                    let pris = $("<span></span><br>").text("Pris: "+element.pris);
                    //prisinfo.append(pris);

                    let obj = JSON.stringify({tid:element.tid, statusflags:parseInt(element.statusflags)});

                    let buttons = $("<span></span>").addClass("manager_buttons");
                    let status_text = $("<p></p>").html(getStatusName(element.statusflags));
                    buttons.append(status_text);

                    // Bookingansvarlig skal ha muligheten til å slette ubehandlede tilbud eller tilbud som har blitt avslått
                    if (bruker.type === 4 && (element.statusflags === 0 || (element.statusflags & 2) === 2  || (element.statusflags & 8  ) === 8)) {
                        let delete_button = $("<button>Slett</button>").addClass("offer_button_delete").val(obj);
                        buttons.append(delete_button);
                    }

                    // Manager og bookingsjef skal kunne akseptere ubehandlede tilbud
                    if ((bruker.type === 3 && (element.statusflags & 1 ) === 1 && (element.statusflags & 4) === 0 )
                        || (bruker.type === 5 && element.statusflags === 0)) {
                        let accept_button = $("<button>Godta</button>").addClass("offer_button_accept").val(obj);
                        buttons.append(accept_button);

                        ventende_tilbud++;
                    }

                    // Manager og bookingsjef skal kunne avslå ubehandlede tilbud
                    if ((bruker.type === 3 && (element.statusflags & 1 ) === 1 && (element.statusflags & 4) === 0 )
                        || (bruker.type === 5 && element.statusflags === 0)) {
                        let reject_button = $("<button>Avslå</button>").addClass("offer_button_reject").val(obj);
                        buttons.append(reject_button);
                    }


                    $("#"+html_id).append(overskrift, dato, start_tid, slutt_tid, pris, sender_navn, buttons);
                    $("#"+html_id).addClass(getStatusColor(element.statusflags)).addClass("tilbud");
                    /*if (debug_mode) {
                let status = $("<span></span>").text("Status: "+element.statusflags);
                $("#"+html_id).append(status);
            }*/
                });
            });

            // Scroll skjermen til tidligere registrerte y-posisjon etter alle tilbud er lagt inn.
            window.scrollTo(0,yoffset);

            // Hvis brukeren har tilbud som må sees på så setter vi knappen til en stil som gjør at brukeren legger merke til det.
            if (ventende_tilbud > 0) {
                $(".tilbuds_notifikasjon").addClass("tilbud_attention");
            } 
            else {
                $(".tilbud_attention").removeClass("tilbud_attention");
            }

        },
        error: function(xmlhttprequest, textstatus, message) {
            if(textstatus==="timeout") {
                alert("Timeout feil, kan ikke koble til databasen");
            } else {
                console.log("Error: "+message);
            }
        }
    });
}

/*
    Bitflags status
    1 : Tilbud godkjent av bookingsjef
    2 : Tilbud avslått av bookingsjef
    4 : Tilbud godkjent av manager
    8 : Tilbud avslått av manager
    */

// Returnerer navnet på css-klasse som bestemmer sitlen til forskjellige statustyper
function getStatusColor(statusflags) {
    if ((statusflags &  2) == 2 || (statusflags & 8 ) == 8) {
        return "reject"
    }
    else if ((statusflags & 1 ) == 1 && (statusflags & 4) == 4) {
        return  "accept";
    }
    else if ((statusflags &  1) == 1 && (statusflags & 4 ) == 0) {
        return "partial-accept";
    }
    else {
        return "unknown"
    }
}

// Returnerer den tekstlige beskrivelsen til forskjellige statustyper
function getStatusName(statusflags) {
    if ((statusflags &  2) == 2 || (statusflags & 8 ) == 8) {
        return "Avslått"
    }
    else if ((statusflags & 1 ) == 1 && (statusflags & 4) == 4) {
        return  "Akseptert av <br> manager";
    }
    else if ((statusflags &  1) == 1 && (statusflags & 4 ) == 0) {
        return "Akseptert av <br> bookingsjef";
    }
    else {
        return "Avventer <br> godkjenning"
    }
}

// Returnerer hvilket bitflag som skal settes ved aksept fra bruker
function getAcceptStatusFlag(usertype) {
    if (usertype == 3) {
        return 4;
    }
    else if (usertype == 5) {
        return 1;
    }
    else {
        console.log("Usertype mismatch: "+usertype);
    }
}

// Returnerer hvilket bitflag som skal settes ved avslag fra bruker
function getRejectStatusFlag(usertype) {
    if (usertype == 3) {
        return 8;
    }
    else if (usertype == 5) {
        return 2;
    }
    else {
        console.log("Usertype mismatch: "+usertype);
    }
}

// Oppdaterer status på tilbud
function updateOfferStatus(obj) {
    $.ajax({ url: '/database.php?method=setOfferStatus',
        data: {tid:obj.tid, status:obj.statusflags},
        type: 'post',
        success: function(output) {
            injectOffers(user);
        },
        error: function(xmlhttprequest, textstatus, message) {
            if(textstatus==="timeout") {
                alert("Timeout feil, kan ikke koble til databasen");
            } else {
                console.log("Error: "+message);
            }
        }
    });
}

// Sletter et tilbud
function deleteOffer(obj) {
    $.ajax({ url: '/database.php?method=deleteOffer',
        data: {tid:obj.tid},
        type: 'post',
        success: function(output) {
            injectOffers(user);
        },
        error: function(xmlhttprequest, textstatus, message) {
            if(textstatus==="timeout") {
                alert("Timeout feil, kan ikke koble til databasen");
            } else {
                console.log("Error: "+message);
            }
        }
    });
}
