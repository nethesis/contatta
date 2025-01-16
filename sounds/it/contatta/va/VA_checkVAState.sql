USE [ContattaDB]
GO

/****** Object:  StoredProcedure [dbo].[TECNONET_checkVAState]    Script Date: 01/06/2016 13:04:09 ******/
DROP PROCEDURE [dbo].[TECNONET_checkVAState]
GO

/****** Object:  StoredProcedure [dbo].[TECNONET_checkVAState]    Script Date: 01/06/2016 13:04:09 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO






/*
Verifica lo stato di un agente e/o di un telefono. Dato come input un numero di telefono si verifica se esiste un operatore con quel numero di telefono 
e il relativo stato. In base allo stato si esibisce un codice di ritorno che indica l'azione successiva da intraprendere nel flusso. Qual'ora il ritorno indichi 
che l'azione successiva sia un trasferimento cieco viene anche verificato lo stato del telefono e se questo risulta occupato viene restituito un codice 
di ritorno differente.


INPUT
	NumTelIN		Numero di telefono chiamato

OUTPUT	
	IdAgent		Identificativo dell'agente
	NumTel		Numero di telefono dell'agente

RETURN
	0			Utente non collegato a Contatta
	1			Utente collegato 
	
*/

CREATE PROCEDURE [dbo].[TECNONET_checkVAState]
@NumTelIN	as	nvarchar(20)

AS
BEGIN ---stored procedure
SET NOCOUNT ON;

DECLARE 
		@Idagent	as int,
		@idstate	as int,
		@IdTelState	as int,
		@RetCode	as int
--inizializzo il return code
Set @RetCode = 0 
		
--cerco agent nella tabella ccactive agent verificando quello ultimo aggiornato nel caso di più righe
SELECT TOP(1) 
		@IdAgent = idagent,
		@Idstate = idstate
	From 	CCActiveAgent
	Where	Numtel = @NumTelIN
	ORDER BY timeLastChangeState DESC

	
-----------------------------------------------------------------
--VERIFICA DELLO STATO DELL'OPERATORE
	
--1 logout
If @Idstate = 1 SET @RetCode = 0 
--2 postlogin
If @Idstate = 2 set @RetCode = 1
--3 in attesa
If @Idstate = 3 Set @RetCode = 1 
--4 Pausa
If @Idstate = 4 Set @RetCode = 0
--5 in Conversazione
If @Idstate = 5 Set @RetCode = 1
--6 Back contact
If @Idstate = 6 Set @RetCode = 1
--7 Post conversazione
If @Idstate = 7 Set @RetCode = 1
--8 Prenotato
If @Idstate = 8 Set @RetCode = 1
--9 Backoffice
If @Idstate = 9 Set @RetCode = 0
--10 Interna Chiamante
If @Idstate = 10 Set @RetCode = 1
--11 Bloccato
If @Idstate = 11 Set @RetCode = 1
--12 Interna Chiamato
If @Idstate = 12 Set @RetCode = 1
--13 Chiamata
If @Idstate = 13 Set @RetCode = 1

--Imposto recordset di uscita e reutn code	
Select 	ISNULL(@idagent,0) as Idagent,
		@NumtelIN as Numtel 

Return @RetCode
    
END --stored procedure

SET NOCOUNT off






GO

