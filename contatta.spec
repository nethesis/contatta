Name: contatta
Version: 0.0.11
Release: 1%{?dist}
Summary: Rest API for FreePBX
Group: Network
License: GPLv2
Source0: %{name}-%{version}.tar.gz
Source1: contatta.tar.gz
BuildRequires: nethserver-devtools, gettext
Buildarch: x86_64
Requires: nethserver-freepbx, nethserver-samba

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


