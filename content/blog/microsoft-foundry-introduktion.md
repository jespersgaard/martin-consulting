---
title: "Introduktion til Microsoft Foundry og din første agent"
description: Hvad er Microsoft Foundry, hvilke begreber skal du kende, og hvordan sætter du din første AI-agent op i portalen? En praktisk introduktion.
pubDate: 2026-11-24
author: Martin Jespersgaard
tags: [AI, Microsoft Foundry, Arkitektur]
image: /images/blog/microsoft-foundry-introduktion.png
imageAlt: Forsidebillede med teksten Microsoft Foundry
draft: false
---

*Microsoft Foundry er Microsofts samlede platform til at bygge AI-agenter, vælge modeller og køre det hele sikkert i produktion.*

Hvis du har arbejdet med Azure OpenAI eller Azure AI Studio, kender du allerede en del af idéen. Foundry samler modeller, agenter og værktøjer ét sted med fælles adgangsstyring, netværk og overvågning. Dette indlæg giver et overblik over de vigtigste begreber og viser, hvordan du får din første agent op at køre.

## Hvad er Microsoft Foundry?

Foundry er en platform for udviklere, der vil bygge AI-agenter, modeller og apps. Den samler tre ting:

| Område | Hvad det er |
| --- | --- |
| **Agenter** | Færdigt hostede *prompt-agenter* eller *hosted agents*, hvor du selv leverer koden |
| **Modeller** | Et katalog med mere end 10.000 modeller fra bl.a. Microsoft, OpenAI, Anthropic og Meta |
| **Værktøjer og viden** | Indbyggede værktøjer, hukommelse og søgning, som agenten kan bruge |

Dertil kommer enterprise-funktioner: sporing (tracing), evalueringer, overvågning, Microsoft Entra-identitet, rollebaseret adgang (RBAC), indholdsfiltre og netværksisolering.

## Grundbegreberne

![Overblik over Foundry: ressource, projekt, modeldeployment og agent med værktøjer](/images/blog/microsoft-foundry-introduktion/arkitektur.png)

- **Foundry-ressource (account):** Den Azure-ressource, der samler styring, netværk og politikker.
- **Projekt:** Et arbejdsområde under ressourcen. Her ligger dine agenter, deployments og forbindelser.
- **Modeldeployment:** En model fra kataloget (fx en GPT-model), som du har udrullet i projektet og giver et navn. Agenten refererer til deploymentet.
- **Agent:** En model plus instruktioner og værktøjer, der kan løse en opgave.
- **Værktøjer:** Fx kodefortolker, filsøgning eller egne funktioner, som agenten kan kalde.
- **Evaluering og tracing:** Måden du tester kvalitet og fejlfinder, hvad agenten faktisk gjorde.

## To slags agenter

| | Prompt-agent | Hosted agent |
| --- | --- | --- |
| **Opsætning** | Instruktioner, model og værktøjer i portal eller SDK | Din egen kode i en container |
| **Drift** | Foundry hoster og kører den | Foundry giver endpoint, skalering, identitet og overvågning |
| **Frihed** | Lav | Høj (fx Microsoft Agent Framework, LangGraph, Semantic Kernel) |
| **Bedst til** | Hurtig start og enkle scenarier | Avanceret logik og fuld kontrol |

Du kan roligt starte med en prompt-agent og gå over til kode, når behovet vokser.

## Sådan sætter du din første agent op

![Seks trin fra Azure-abonnement, over projekt, instruktioner og værktøjer, til en testet agent](/images/blog/microsoft-foundry-introduktion/opsaetning.png)

1. **Forudsætninger:** Du skal have et Azure-abonnement og en rolle, der må oprette projektet, fx Contributor eller Owner på abonnementet, eller den dedikerede Foundry-rolle.
2. **Åbn portalen** på [ai.azure.com](https://ai.azure.com) og vælg at oprette en agent.
3. **Navngiv projektet.** Under *Advanced options* kan du ændre standardindstillinger som region.
4. **Vent på provisioneringen.** Foundry opretter ressource og projekt, udruller en standardmodel og laver en første agent.
5. **Skriv instruktioner** i agent-playgrounden, fx: *"Du er en hjælpsom agent, der besvarer spørgsmål om vores produkter. Svar kort og på dansk."*
6. **Tilføj værktøjer og viden**, hvis agenten skal søge i dokumenter eller kalde funktioner.
7. **Test i playgrounden**, og justér instruktionerne ud fra svarene.
8. **Giv adgang:** Medlemmer, der skal redigere agenter, skal have rollen *Foundry User* på projektet.

Når prototypen virker, kan du kalde agenten fra kode via Foundry SDK'erne (Python, C#, JavaScript og Java).

## Gode råd fra start

- **Skriv præcise instruktioner.** Beskriv rolle, tone, afgrænsning og hvad agenten ikke må.
- **Giv minimale rettigheder.** Brug RBAC og kun de værktøjer, agenten reelt har brug for.
- **Evaluér løbende.** Lav en lille testsamling af spørgsmål, og kør den, hver gang du ændrer instruktioner eller model.
- **Hold øje med omkostninger.** Modelforbrug afregnes pr. brug, så vælg en passende model og følg forbruget.
- **Tjek status på funktioner.** Nogle dele af platformen er i preview, og de ældre "classic"-agenter er under udfasning, så brug den nye Agent Service til nye projekter.

## Konklusion

Foundry gør det nemt at komme fra idé til en kørende agent: opret et projekt, udrul en model, skriv instruktioner og test. Bagefter får du styring, sporing og sikkerhed med i pakken, når agenten skal i produktion. Start småt med en prompt-agent, mål kvaliteten, og tilføj først kode og værktøjer, når du har brug for dem.
