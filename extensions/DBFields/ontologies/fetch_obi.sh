#!/bin/sh
#This script with fetch the latest OBI ontology files from the repository
#where they are broken up into their respective branches
#The URLS are http escaped, 
#then, they are converted OWL->OBO using Chris' script
#Loading into OBOEdit gets messed up if some of the xsd elements aren't
#accepable, so i'm just getting rid of them.


#uncomment the next line for testing
set -x

#The Biomaterial Branch
Biomaterial='http://obi.svn.sourceforge.net/viewvc/*checkout*/obi/trunk/src/ontology/branches/Biomaterial.owl'
esc_Biomaterial=`echo $Biomaterial | sed s/'\/'/'\%2F'/g | sed s/':'/'\%3A'/g` 
BiomaterialOut='obi-biomaterial.obo'

#The Data Transformation Branch
DataTransformation='http://obi.svn.sourceforge.net/viewvc/*checkout*/obi/trunk/src/ontology/branches/DataTransformation.owl'
esc_Data=`echo $DataTransformation | sed s/'\/'/'\%2F'/g | sed s/':'/'\%3A'/g` 
DataTransformationOut='obi-data.obo'

#The Digital Entity Branch
DigitalEntity='http://obi.svn.sourceforge.net/viewvc/*checkout*/obi/trunk/src/ontology/branches/DigitalEntityPlus.owl'
esc_Digital=`echo $DigitalEntity | sed s/'\/'/'\%2F'/g | sed s/':'/'\%3A'/g` 
DigitalEntityOut='obi-digitalentity.obo'

#The process branch
Process='http://obi.svn.sourceforge.net/viewvc/*checkout*/obi/trunk/src/ontology/branches/PlanAndPlannedProcess.owl'
esc_Process=`echo $Process | sed s/'\/'/'\%2F'/g | sed s/':'/'\%3A'/g` 
ProcessOut='obi-process.obo'

#OBI Full
OBI='http://purl.obofoundry.org/obo/obi.owl'
esc_OBI=`echo $OBI | sed s/'\/'/'\%2F'/g | sed s/':'/'\%3A'/g` 
OBIOut='obi.obo'

format='obo'
style='obi'
follow_imports='1'

converter_base='http://www.berkeleybop.org/obo-conv.cgi'


echo `curl $converter_base?url=$esc_Biomaterial\&format=$format\&style=$style\&follow_imports=$follow_imports > $BiomaterialOut`
echo `curl $converter_base?url=$esc_Data\&format=$format\&style=$style\&follow_imports=$follow_imports > $DataTransformationOut`
echo `curl $converter_base?url=$esc_Digital\&format=$format\&style=$style\&follow_imports=$follow_imports > $DigitalEntityOut`
echo `curl $converter_base?url=$esc_Process\&format=$format\&style=$style\&follow_imports=$follow_imports > $ProcessOut`
echo `curl $converter_base?url=$esc_OBI\&format=$format\&style=$style\&follow_imports=$follow_imports > $OBIOut`

temp='temp.obo'
echo `grep -v 'xsd' $BiomaterialOut > $temp`
echo `mv $temp $BiomaterialOut`
echo `grep -v 'xsd' $DataTransformationOut > $temp`
echo `mv $temp $DataTransformationOut`
echo `grep -v 'xsd' $DigitalEntityOut > $temp`
echo `mv $temp $DigitalEntityOut`
echo `grep -v 'xsd' $ProcessOut > $temp`
echo `mv $temp $ProcessOut`
echo `grep -v 'xsd' $OBIOut > $temp`
echo `mv $temp $OBIOut`

