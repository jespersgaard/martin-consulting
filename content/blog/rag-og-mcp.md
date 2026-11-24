---
title: "RAG og MCP: Forskellen, og hvorfor du sandsynligvis har brug for begge"
description: RAG hjælper AI med at vide mere, MCP hjælper den med at gøre mere. Sådan adskiller de to sig, og sådan kombinerer du dem.
pubDate: 2026-11-24
author: Martin Jespersgaard
tags: [AI, RAG, MCP, Arkitektur]
image: /images/blog/rag-mcp.png
imageAlt: Forsidebillede med teksten RAG og MCP
draft: false
---

*RAG hjælper en AI-model med at **vide** mere. MCP hjælper den med at **gøre** mere.*

Når man arbejder med AI-agenter, støder man hurtigt på to forkortelser: **RAG** (Retrieval-Augmented Generation) og **MCP** (Model Context Protocol). De bliver ofte stillet op som konkurrenter, men de løser to forskellige problemer. Dette indlæg forklarer begge dele, sammenligner dem og viser, hvordan de kombineres i praksis.

## Problemet: Modellen mangler kontekst

En stor sprogmodel (LLM) har to grundlæggende begrænsninger:

- **Vidensgrænse:** Den kender kun det, den blev trænet på.
- **Hallucinationer:** Når den ikke ved noget, gætter den ofte med stor selvtillid.

Både RAG og MCP løser det samme overordnede problem, nemlig at give modellen kontekst, den ikke blev trænet på. De gør det bare på to meget forskellige måder.

## Hvad er RAG?

**Retrieval-Augmented Generation** henter relevante tekststykker fra en forudbygget vidensbase og lægger dem ind i prompten, før modellen svarer. Modellens svar bliver dermed forankret i rigtige dokumenter.

### Sådan fungerer en RAG-pipeline

1. **Indlæsning (ingestion):** Dokumenter (PDF'er, wikis, FAQ'er, manualer) parses og opdeles i bidder (*chunking*).
2. **Embedding:** Hver bid omdannes til en vektor af en embedding-model.
3. **Indeksering:** Vektorerne gemmes i en vektordatabase (fx Qdrant, pgvector, Chroma).
4. **Hentning:** Spørgsmålet embeddes, og de mest lignende bidder findes via semantisk søgning (top-k).
5. **Berigelse (augmentation):** De fundne bidder tilføjes til prompten.
6. **Generering:** LLM'en svarer ud fra spørgsmålet og den hentede kontekst.

**Offline: indeksering**

![RAG offline: dokumenter chunkes, embeddes og gemmes i en vektordatabase](/images/blog/rag-mcp/rag-offline.png)

**Online: forespørgsel**

![RAG online: spørgsmålet embeddes, relevante bidder hentes og sendes til LLM'en](/images/blog/rag-mcp/rag-online.png)

### Semantisk søgning vs. nøgleordssøgning

Vektorsøgning finder indhold, der *betyder* det samme, selv med andre ord. Men den er svag på præcise identifikatorer som ordrenumre, SKU'er og fejlkoder. Derfor bruger de fleste produktionssystemer **hybrid søgning**: semantisk søgning kombineret med nøgleordssøgning (fx BM25) og ofte en reranker til sidst.

### RAG fungerer bedst, når

- vidensbasen er stor, men ikke ændrer sig hver dag
- data er ustruktureret (dokumenter, artikler, wikis)
- lav svartid er vigtigere end absolut friskhed
- du kan bære omkostningen ved indeksering og embeddings

### RAG's faldgruber

- **Dårlig chunking** giver irrelevant eller afkortet kontekst.
- **Forældet indeks:** Ændres kilderne, skal indekset opdateres.
- **Adgangsstyring:** Når data ligger i en vektordatabase, skal rettigheder følge med, ellers kan brugere få svar baseret på dokumenter, de ikke må se.
- **Ingen handlinger:** RAG kan kun læse. Den kan ikke opdatere en post eller starte en proces.

## Hvad er MCP?

**Model Context Protocol** er en åben standard introduceret af Anthropic i november 2024. Den standardiserer, hvordan AI-applikationer forbinder til eksterne værktøjer, datakilder og tjenester. Det beskrives ofte som **"USB-C til AI"**.

### N×M-problemet

Uden en fælles standard kræver N modeller og M systemer op til N×M skræddersyede integrationer. Med MCP implementerer hver klient protokollen én gang, og hvert system eksponerer én MCP-server. Det giver **N+M** i stedet.

![Uden MCP kræver N modeller og M systemer N×M integrationer; med MCP kun N+M](/images/blog/rag-mcp/n-x-m.png)

### Arkitektur: host, klient og server

- **Host:** AI-applikationen brugeren arbejder i (IDE, chat-app, egen applikation).
- **Klient:** Komponent i hosten, der holder forbindelsen til én MCP-server.
- **Server:** Letvægtstjeneste, der udstiller funktioner til klienten.

![Sekvens: bruger, host, MCP-server og live system](/images/blog/rag-mcp/mcp-sekvens.png)

### De tre primitiver

| Primitiv | Beskrivelse | Eksempel |
|---|---|---|
| **Tools** | Funktioner modellen kan kalde for at hente data eller udføre handlinger | `get_customer(id)`, `create_ticket(...)` |
| **Resources** | Strukturerede kontekstobjekter identificeret ved en URI | En fil, en databasepost |
| **Prompts** | Foruddefinerede skabeloner serveren udstiller | En standardiseret "opsummér sag"-opgave |

MCP understøtter flere transportlag: **stdio** til lokale forbindelser og **HTTP (streamable HTTP/SSE)** til fjernservere.

### MCP fungerer bedst, når

- data ændrer sig hurtigere, end du kan genindeksere
- du har brug for bruger- eller sessionsspecifik kontekst
- det ville være problematisk at kopiere data til et indeks (persondata, økonomi, sundhed)
- du har brug for deterministisk adgang frem for omtrentlig lighed
- modellen skal **handle**, ikke kun læse

### MCP's faldgruber

- **Sikkerhed:** Et værktøj med skriveadgang er en reel angrebsflade. Brug mindst mulige rettigheder, bekræftelse ved destruktive handlinger og beskyt mod *prompt injection* i data, der returneres fra værktøjer.
- **Latens:** Hvert kald afhænger af det underliggende systems svartid.
- **Værktøjsoverflod:** Mange tools i konteksten forvirrer modellen og æder tokens.
- **Tillid til servere:** Tredjeparts MCP-servere skal vurderes som al anden kode, der kører med adgang til dine data.

## RAG vs. MCP side om side

| | RAG | MCP |
|---|---|---|
| **Kerneidé** | Lær modellen mere | Giv modellen adgang og handlekraft |
| **Datatype** | Statisk, ustruktureret (PDF, wiki, FAQ) | Dynamisk, struktureret (API, database, SaaS) |
| **Metode** | Embedding + vektorsøgning | Værktøjskald ved kørsel |
| **Friskhed** | Så frisk som sidste indeksering | Realtid |
| **Latens** | Lav (indekseret søgning) | Middel (afhænger af API'et) |
| **Datalagring** | Kopi af data i vektordatabase | Ingen kopi, adgang ved kørsel |
| **Kan handle?** | Nej, kun læse | Ja, læse og skrive |
| **Opsætning** | Chunking, embedding, indeksering | Definér og registrér tool-skemaer |
| **Typiske cases** | Dokument-Q&A, vidensassistenter | CRM-opslag, analyse-bots, statusopslag, automatisering |

## Hvornår skal jeg vælge hvad?

![Beslutningstræ: vælg MCP, RAG eller begge](/images/blog/rag-mcp/beslutning.png)

Tommelfingerregel: **stabil viden → RAG, levende data og handlinger → MCP, begge dele → kombinér.**

## Det stærkeste setup: RAG + MCP sammen

I praksis bruger de fleste produktionssystemer begge teknikker. Et eksempel på en kundesupport-assistent:

- **RAG** henter fejlfindingsvejledninger og produktdokumentation fra vidensbasen.
- **MCP** slår kundens aktuelle abonnement, SLA og åbne sager op i realtid.
- **MCP** kan oprette eller opdatere en sag, når problemet er løst eller skal eskaleres.

![Hybrid arkitektur: AI-agent med både RAG-lag og MCP-lag](/images/blog/rag-mcp/hybrid.png)

Resultatet er svar, der både er **fagligt velfunderede** (RAG) og **personlige og opdaterede** (MCP).

Man kan også lade RAG selv være et MCP-værktøj: en MCP-server udstiller `search_knowledge_base(query)`, og agenten beslutter selv, hvornår den skal slå op. Så får du ét ensartet grænseflade-lag, og modellen vælger dynamisk mellem viden og live data.

## Best practices

1. **Start med behovet, ikke teknologien.** Er spørgsmålet "hvad står der?" eller "hvad er tilstanden nu?" eller "gør dette"?
2. **Brug hybrid søgning og reranking** i RAG, ikke kun ren vektorsøgning.
3. **Evaluér løbende.** Mål retrieval-kvalitet (recall, relevans) og værktøjskald (korrekt valg af tool, korrekte argumenter).
4. **Mindst mulige rettigheder.** Giv MCP-værktøjer kun den adgang, de skal bruge, og lad dem køre med den kaldende brugers rettigheder.
5. **Mennesket i løkken** ved irreversible handlinger.
6. **Log alt.** Kilder for RAG-svar og alle værktøjskald skal kunne spores.
7. **Respektér adgangsstyring** i begge lag. Et RAG-indeks må ikke blive en bagdør uden om rettigheder.
8. **Hold værktøjslisten kort og velbeskrevet,** så modellen vælger rigtigt.

## Konklusion

RAG og MCP er ikke enten/eller. RAG giver modellen **hukommelse og baggrundsviden**; MCP giver den **øjne, hænder og aktuel viden**. Brug RAG til det, der er stabilt og dokumentbaseret, MCP til det, der er levende og handlingsorienteret, og kombinér dem, når du bygger agenter, der skal være både kloge og nyttige.

