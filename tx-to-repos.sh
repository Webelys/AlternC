#!/bin/bash -x

#------------------------------------------------------------
# Short doc: launch this when people said they translated 
# the program in Transifex, so that their translation appears
# in the production package.
#------------------------------------------------------------

# Long doc: 
# Take each sub-project of AlternC 
# (EXPECTED IN PARENT FOLDERS of alternc/trunk/)
# (yes, one day we will be united again ;) )
# and get the TRANSLATED strings from transifex
# then put them at the right places in the repositories
# and commit everything using svn

tx pull -a -f

langs="fr_FR de_DE en_US es_ES pt_BR it_IT nl_NL"

for lang in $langs
do
    echo "doing lang $lang"
    cp "lang/${lang}.po" "bureau/locales/$lang/LC_MESSAGES/alternc"
    sublang="`echo $lang | cut -c 1-2`"
    # merge the po for debconf into the relevant file for the modules : 
    if [ "$lang" != "en_US" ]
    then
	cat "debian/po/${sublang}.po" | sed -e 's/msgstr ""/msgstr "**DUMMY**"/'  >tmp-debconf.po
	msgcat --use-first --less-than=3 --more-than=1 -o tmp.po  "lang/${lang}.po" "tmp-debconf.po"
	rm "tmp-debconf.po"
	mv -f tmp.po "debian/po/${sublang}.po"
	cat "../../alternc-mailman/trunk/debian/po/${sublang}.po" | sed -e 's/msgstr ""/msgstr "**DUMMY**"/'  >tmp-debconf.po
	msgcat --use-first --less-than=3 --more-than=1 -o tmp.po  "lang/${lang}.po" "tmp-debconf.po"
	rm "tmp-debconf.po"
	mv -f tmp.po "../../alternc-mailman/trunk/debian/po/${sublang}.po"
    fi
    echo "done"
done

if [ "$1" != "nocommit" ] 
then 
# Now committing 
    svn commit -m "Updating language files from Transifex"
    pushd ../../alternc-mailman/trunk
    svn commit -m "Updating language files from Transifex"
    popd
fi


