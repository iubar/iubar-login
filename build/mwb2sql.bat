@echo off
REM generate sql from mwb
REM usage: mwb2sql.bat {.mwb file} {output file}

SET WORKBENCH="C:\Program Files\MySQL\MySQL Workbench 6.3 CE\MySQLWorkbench.exe"
SET OUTPUT=%~f2
%WORKBENCH% ^
  -open %~f1 ^
  -run-python "import os;import grt;from grt.modules import DbMySQLFE as fe;c = grt.root.wb.doc.physicalModels[0].catalog;fe.generateSQLCreateStatements(c, c.version, {});fe.createScriptForCatalogObjects(os.getenv('OUTPUT'), c, {})" ^
  -quit-when-done
  
  
REM Il codice python in linea corrisponde al seguente frammento
REM   import os;
REM   import grt;
REM   from grt.modules import DbMySQLFE as fe;
REM   c = grt.root.wb.doc.physicalModels[0].catalog;
REM   fe.generateSQLCreateStatements(c, c.version, {});
REM   fe.createScriptForCatalogObjects(os.getenv('OUTPUT'), c, {})
  