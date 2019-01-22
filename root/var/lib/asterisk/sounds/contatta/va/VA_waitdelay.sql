USE [ContattaDB]
GO

/****** Object:  StoredProcedure [dbo].[TECNONET_waitDelay]    Script Date: 01/06/2016 13:03:38 ******/
DROP PROCEDURE [dbo].[TECNONET_waitDelay]
GO

/****** Object:  StoredProcedure [dbo].[TECNONET_waitDelay]    Script Date: 01/06/2016 13:03:38 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO


CREATE PROCEDURE [dbo].[TECNONET_waitDelay]
@waitSecond as int

AS
BEGIN ---stored procedure
SET NOCOUNT ON;

DECLARE 
		@RetCode	as int,
		@Delay		as DATETIME
--inizializzo il return code
Set @RetCode = 0 
		
SELECT @Delay = dateadd(SECOND, @waitSecond, convert(DATETIME, 0))
WAITFOR DELAY @Delay

--Imposto recordset di uscita e reutn code	
Select 	@waitSecond as waitSecond 

Return @RetCode
    
END --stored procedure

SET NOCOUNT off






GO

