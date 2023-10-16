Name: contatta
Version: 1.3.0
Release: 1%{?dist}
Summary: Rest API for FreePBX
Group: Network
License: GPLv2
Source0: %{name}-%{version}.tar.gz
Source1: contatta.tar.gz
BuildRequires: nethserver-devtools, gettext
Buildarch: x86_64
Requires: nethserver-freepbx, nethserver-samba, lame, asterisk-codecs-g729

%description
Rest API for FreePBX

%prep
%setup


%build
perl createlinks
for PO in $(find ./ -name "*\.po" | grep 'i18n\/[a-z][a-z]_[A-Z][A-Z]')
    do msgfmt -o $(echo ${PO} | sed 's/\.po$/.mo/g') ${PO}
done

%install
rm -rf %{buildroot}
(cd root; find . -depth -print | cpio -dump %{buildroot})
mkdir -p %{buildroot}/usr/src/nethvoice/modules
mv %{S:1} %{buildroot}/usr/src/contatta/modules/

%{genfilelist} %{buildroot} \
> %{name}-%{version}-filelist


%clean
rm -rf %{buildroot}

%files -f %{name}-%{version}-filelist
%defattr(-,root,root,-)
%dir %{_nseventsdir}/%{name}-update
%doc

%changelog
* Mon Oct 16 2023 Stefano Fancello <stefano.fancello@nethesis.it> - 1.3.0-1
- recording line added to the custom dialplan

* Mon Jul 03 2023 Stefano Fancello <stefano.fancello@nethesis.it> - 1.2.0-1
- predictive added to the webservice

* Tue Mar 28 2023 Stefano Fancello <stefano.fancello@nethesis.it> - 1.1.2-1
- Take trunk credentials from input

* Fri Oct 07 2022 Stefano Fancello <stefano.fancello@nethesis.it> - 1.1.1-1
- Merge pull request #13 from hoverflow/contatta-mod-202210a
- added another option to the monitor exec combobox
- dialplan updated

* Thu May 12 2022 Stefano Fancello <stefano.fancello@nethesis.it> - 1.1.0-1
- Merge pull request #9 from nethesis/trunk_id
- Merge pull request #10 from nethesis/Stell0-patch-1
- Merge pull request #11 from nethesis/retrieve
- Merge pull request #12 from nethesis/contactuser
- Add requires for g729 package
- Fix contacuser always null returned by trunks GET
- Use default retrieveHelper.sh instead of custom ones
- Delete codec_g729.so
- Overwrite trunk if id is specified

* Tue Mar 08 2022 Stefano Fancello <stefano.fancello@nethesis.it> - 1.0.0-1
- Add AMD to dialplan
- Add SetCID APIS
- Add customdest APIs
- Add fields to GET /trunk and return route_id in POST /outboundroute
- Add autentication and registration fields in README
- Add POST /trunk/<trunkid>/disabled/<on|off>
- Update README with new APIs documentation
- Add additional APIs

* Thu Jul 22 2021 Stefano Fancello <stefano.fancello@nethesis.it> - 0.0.14-1
- Fix dialplan

* Fri Apr 30 2021 Stefano Fancello <stefano.fancello@nethesis.it> - 0.0.13-1
- dialplan modified in order to resolve REFER NOTIFY latency

* Fri Oct 04 2019 Stefano Fancello <stefano.fancello@nethesis.it> - 0.0.12-1
- Added mp3 support

* Tue Jun 04 2019 Stefano Fancello <stefano.fancello@nethesis.it> - 0.0.11-1
- added intrusion line(s) handler, modified route points/wave lines handler

* Mon Mar 04 2019 Stefano Fancello <stefano.fancello@nethesis.it> - 0.0.8-1
- Automatically set NethServer certificates

* Thu Feb 14 2019 Stefano Fancello <stefano.fancello@nethesis.it> - 0.0.7-1
- Install required modules if they are missing
- Fixed dialplan

* Wed Feb 13 2019 Stefano Fancello <stefano.fancello@nethesis.it> - 0.0.6-1
- Automatically configure API extension for WebRTC
- Use a function to write asterisk sip table
- createlinks: fix contatta-update event

* Tue Feb 05 2019 Stefano Fancello <stefano.fancello@nethesis.it> - 0.0.5-1
- Open WebRTC ports by default

* Tue Feb 05 2019 Stefano Fancello <stefano.fancello@nethesis.it> - 0.0.4-1
- Enable the mini-HTTP Server and TLS for the mini-HTTP Server


