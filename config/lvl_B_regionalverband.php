<?php 


# Sparten im Regionalverband
# BSG im Regionalverband
# Mitglieder in den Sparten 
# Rechteverwaltung: BSG

# Statistik: Mitglieder in Sparten


######################################################################################################

# Sparten im Regionalverband
$anzuzeigendeDaten[] = array(
    "tabellenname" => "b_sparte",
    "auswahltext" => "Sparten im Regionalverband",
    "writeaccess" => true,
    "query" => "SELECT s.id as id, s.Verband as Verband, s.Sparte, s.Sportart as Sportart
        FROM b_sparte as s
        WHERE FIND_IN_SET(s.id, berechtigte_elemente($uid, 'sparte')) > 0 or Verband IS NULL
        order by id desc;
    ",
    "referenzqueries" => array(
        "Verband" => "SELECT v.id, v.Verband as anzeige
        FROM b_regionalverband as v
        WHERE FIND_IN_SET(v.id, berechtigte_elemente($uid, 'verband')) > 0
        ORDER BY anzeige;
        ",
        "Sportart" => "SELECT id, CONCAT (Sportart,' (',Sportart_Nr,')') as anzeige from b___sportart ORDER BY anzeige;"
    ),
    "spaltenbreiten" => array(
        "Verband"                       => "380",
        "Sparte"                        => "250",  
        "Sportart"                      => "250"
    )
);


# BSG im Regionalverband
$anzuzeigendeDaten[] = array(
    "tabellenname" => "b_bsg",
    "auswahltext" => "BSG im Regionalverband",
    "writeaccess" => true,
    "hinweis" => "<b>RE </b> = Rechnungsempfänger. In diese Spalten bitte eintragen, wohin eventuelle Rechnungen geschickt werden sollen.",
    "query" => "SELECT 
        b.id as id,
        b.Verband as Verband,
        b.BSG as BSG,
        Ansprechpartner,
        RE_Name,
        RE_Name2,
        RE_Strasse_Nr,
        RE_Strasse2,
        RE_PLZ_Ort
        FROM b_bsg as b
        WHERE (
            FIND_IN_SET(b.id, berechtigte_elemente($uid, 'BSG')) > 0 AND
            FIND_IN_SET(b.Verband, berechtigte_elemente($uid, 'verband')) > 0 )
        or Verband IS NULL
        order by id desc;
    ",
    "referenzqueries" => array(
        "Verband" => "SELECT v.id, v.Verband as anzeige
        from b_regionalverband as v
        WHERE FIND_IN_SET(v.id, berechtigte_elemente($uid, 'verband')) > 0
        ORDER BY anzeige;
        ",
        "Ansprechpartner" => "SELECT m.id, CONCAT(Nachname, ', ', Vorname) as anzeige 
                                from b_mitglieder as m
                                join b_bsg as b on b.id=m.BSG
                                
                                WHERE FIND_IN_SET(m.id, berechtigte_elemente($uid, 'mitglied')) > 0
                                order by anzeige;"
    ),
    "spaltenbreiten" => array(
        "Verband"                       => "380",
        "BSG"                           => "320",  
        "Ansprechpartner"               => "200",  
        "RE_Name"                       => "200",  
        "RE_Name2"                      => "200",  
        "RE_Strasse_Nr"                 => "200",  
        "RE_Strasse2"                   => "200",  
        "RE_PLZ_Ort"                    => "200"
    ) 
);


# BSG-Rechte - Wer darf die Mitglieder welcher BSG editieren? 
# Ich sehe nur BSG von Verbänden, zu deren Ansicht ich berechtigt bin
$anzuzeigendeDaten[] = array(
    "tabellenname" => "b_bsg_rechte",
    "auswahltext" => "Rechteverwaltung: BSG",
    "hinweis" => "Berechtigt angemeldete Nutzer, Mitglieder einer BSG zu sehen und zu bearbeiten.",
    "writeaccess" => true,
    "query" => "SELECT br.id as id, br.BSG, br.Nutzer
                from b_bsg_rechte as br 
                left join b_bsg as b on br.BSG = b.id
                WHERE FIND_IN_SET(b.id, berechtigte_elemente($uid, 'BSG')) > 0 OR Nutzer IS NULL;
                ",
    "referenzqueries" => array(
        "BSG" => "SELECT b.id as id, b.BSG as anzeige
                    FROM b_bsg as b
                    WHERE FIND_IN_SET(b.id, berechtigte_elemente($uid, 'BSG')) > 0
                    ORDER BY anzeige;",
        "Nutzer" => "SELECT id, mail as anzeige from y_user ORDER BY anzeige;"
    ),
    "spaltenbreiten" => array(
        "BSG"                       => "300",
        "Nutzer"                    => "300"
    )
);

######################################################################################################

# Statistik: Mitglieder in Sparten
$statistik[] = array(
    "titel" => "Mitglieder in Sparten im Regionalverband",
    "query" => "SELECT s.Sparte, count(mis.Mitglied) as Mitglieder
                from b_mitglieder_in_sparten as mis
                join b_sparte as s on s.id = mis.Sparte
                WHERE FIND_IN_SET(s.id, berechtigte_elemente($uid, 'sparte')) > 0
                group by s.Sparte;
                ",
    "typ"   => "torte"
);


?>