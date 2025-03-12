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
    "query" => "SELECT s.id as id, s.Verband as Verband, s.Sparte, s.Spartenleiter as Spartenleiter, s.Sportart as Sportart
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
        "Spartenleiter" => "SELECT m.id, CONCAT(Nachname, ', ', Vorname) as anzeige
        from b_mitglieder as m
        ORDER BY anzeige;",
        "Sportart" => "SELECT id, CONCAT (Sportart,' (',Sportart_Nr,')') as anzeige from b___sportart ORDER BY anzeige;"
    ),
    "spaltenbreiten" => array(
        "Verband"                       => "380",
        "Sparte"                        => "250",  
        "Spartenleiter"                 => "250",  
        "Sportart"                      => "250"
    )
);

# Mitglieder in Sparten
$anzuzeigendeDaten[] = array(
    "tabellenname" => "b_mitglieder_in_sparten",
    "auswahltext" => "Sparten und ihre Mitglieder",
    "writeaccess" => false,
    "query" => "SELECT mis.id as id, v.Kurzname as Verband, s.Sparte as Sparte, b.BSG as BSG, concat (m.Vorname,' ',m.Nachname) as Mitglied
                from b_mitglieder_in_sparten as mis
                join b_sparte as s on mis.Sparte = s.id
                join b_regionalverband as v on s.Verband = v.id
                join b_mitglieder as m on mis.Mitglied = m.id
                join b_bsg as b on mis.BSG = b.id
                WHERE FIND_IN_SET(v.id, berechtigte_elemente ($uid, 'verband')) > 0
                order by Verband, Sparte, BSG, Mitglied;"
);

# BSG im Regionalverband
$anzuzeigendeDaten[] = array(
    "tabellenname" => "b_bsg",
    "auswahltext" => "BSG im Regionalverband",
    "writeaccess" => true,
    "query" => "SELECT 
        id, Verband, VKZ, BSG 
        from b_bsg as b
        WHERE FIND_IN_SET(b.Verband, berechtigte_elemente($uid, 'verband')) > 0 
        order by id desc;
    ",
    "referenzqueries" => array(
        "Verband" => "SELECT v.id, v.Verband as anzeige
        from b_regionalverband as v
        WHERE FIND_IN_SET(v.id, berechtigte_elemente($uid, 'verband')) > 0
        ORDER BY anzeige;
        "
    ),
    "spaltenbreiten" => array(
        "Verband"                       => "380",
        "VKZ"                           => "100",
        "BSG"                           => "320"
    ) 
);


# BSG im Regionalverband
$anzuzeigendeDaten[] = array(
    "tabellenname" => "b_bsg",
    "auswahltext" => "BSG: Ansprechpartner und Rechnungsdaten",
    "import" => false,
    "writeaccess" => false,
    "hinweis" => "<b>RE </b> = Rechnungsempfänger. In diese Spalten bitte eintragen, wohin eventuelle Rechnungen geschickt werden sollen.",
    "query" => "SELECT 
        b.id as id,
        b.BSG as BSG,
        Ansprechpartner,
        RE_Name,
        RE_Name2,
        RE_Strasse_Nr,
        RE_Strasse2,
        RE_PLZ_Ort
        FROM b_bsg as b
        WHERE 
            FIND_IN_SET(b.Verband, berechtigte_elemente($uid, 'verband')) > 0 
        order by id desc;
    ",
    "referenzqueries" => array(
        "Ansprechpartner" => "SELECT m.id, CONCAT(Nachname, ', ', Vorname) as anzeige 
                                from b_mitglieder as m
                                join b_bsg as b on b.id=m.BSG
                                -- WHERE FIND_IN_SET(m.id, berechtigte_elemente($uid, 'mitglied')) > 0
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
    "auswahltext" => "BSG: Rechteverwaltung",
    "hinweis" => "Berechtigt angemeldete Nutzer, Mitglieder einer BSG zu sehen und zu bearbeiten.",
    "writeaccess" => true,
    "query" => "SELECT br.id as id, br.BSG, br.Nutzer
                from b_bsg_rechte as br 
                left join b_bsg as b on br.BSG = b.id
                WHERE FIND_IN_SET(b.Verband, berechtigte_elemente($uid, 'verband')) > 0;
                ",
    "referenzqueries" => array(
        "BSG" => "SELECT b.id as id, b.BSG as anzeige
                    FROM b_bsg as b
                    WHERE FIND_IN_SET(b.Verband, berechtigte_elemente($uid, 'verband')) > 0
                    ORDER BY anzeige;",
        "Nutzer" => "SELECT id, mail as anzeige from y_user ORDER BY anzeige;"
    ),
    "spaltenbreiten" => array(
        "BSG"                       => "300",
        "Nutzer"                    => "300"
    )
);

# Mitglieder nach Sportarten / Turniereinladungen
$anzuzeigendeDaten[] = array(
    "tabellenname" => "b_mitglieder_in_sparten",
    "auswahltext" => "Mailingliste Turniereinladungen",
    "writeaccess" => false,
    "hinweis" => "Es werden nur Mitglieder angezeigt, die ´Mailbenachrichtigung´ aktiviert haben.",
    "query" => "SELECT m.id as id, m.Mail as Mail, YEAR(m.Geburtsdatum) as Jahrgang, g.auswahl as 'm/w/d', sa.Sportart_Nr, sa.Sportart
                FROM b_mitglieder_in_sparten    as mis
                JOIN b_mitglieder               as m  ON m.id  = mis.Mitglied
                JOIN b_bsg                      as b  ON b.id  = m.BSG
                JOIN b_regionalverband          as r  ON r.id  = b.Verband
                JOIN b_sparte                   as s  ON s.id  = mis.Sparte 
                JOIN b___sportart               as sa ON sa.id = s.Sportart
                LEFT JOIN b___geschlecht        as g  ON g.id  = m.Geschlecht
                WHERE m.Mailbenachrichtigung=1"
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