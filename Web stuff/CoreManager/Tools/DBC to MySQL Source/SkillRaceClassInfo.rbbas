#tag Class
Protected Class SkillRaceClassInfo
Inherits RunnerClass
	#tag Event
		Sub Run()
		  do
		    dim ID As integer
		    dim SkillLine As integer
		    dim ChrRaces As UInt32
		    dim ChrClasses As UInt32
		    dim Flags As UInt32
		    dim ReqLevel As UInt32
		    dim SkillTierID As UInt32
		    dim SkillCostID As integer
		    
		    dim red, blue As integer
		    
		    if record < recordCount then
		      Window1.ProgSkillRaceClassInfo.text = str(Record) + "/" + str(recordCount - 1)
		      blue = floor((Record / recordCount) * 255)
		      red = 255 - blue
		      Window1.ProgSkillRaceClassInfo.TextColor = RGB(red, 0, blue)
		      Window1.ProgSkillRaceClassInfo.Refresh
		      
		      ID = b.ReadInt32
		      SkillLine = b.ReadInt32
		      ChrRaces = b.ReadUInt32
		      ChrClasses = b.ReadUInt32
		      Flags = b.ReadUInt32
		      ReqLevel = b.ReadUInt32
		      SkillTierID = b.ReadUInt32
		      SkillCostID = b.ReadInt32
		      
		      dim query as string
		      query = "INSERT INTO skillraceclassinfo VALUES(" + str(ID) + ", " + str(SkillLine) + ", " + str(ChrRaces) + ", " + str(ChrClasses) + ", " + str(Flags) + ", " _
		      + str(ReqLevel) + ", " + str(SkillTierID) + ", " + str(SkillCostID) + ")"
		      
		      db.SQLExecute(query)
		      
		      if db.ErrorMessage <> "" then
		        Window1.TextArea1.text = Window1.TextArea1.text + chr(13) + db.ErrorMessage + "(" + query + ")"
		        exit do
		      end if
		      Record = Record + 1
		    else
		      Window1.ProgSkillRaceClassInfo.text = "COMPLETE"
		      Window1.ProgSkillRaceClassInfo.TextColor = &c0000FF
		      Window1.ProgSkillRaceClassInfo.Refresh
		      exit do
		    end if
		  loop
		End Sub
	#tag EndEvent


	#tag Note, Name = LICENSE
		
		CoreManager, PHP Front End for ArcEmu, MaNGOS, and TrinityCore
		Copyright (C) 2010-2013  CoreManager Project
		Copyright (C) 2009-2010  ArcManager Project
		
		This program is free software: you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation, either version 3 of the License, or
		(at your option) any later version.
		
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.
		
		You should have received a copy of the GNU General Public License
		along with this program.  If not, see <http://www.gnu.org/licenses/>.
	#tag EndNote


	#tag ViewBehavior
		#tag ViewProperty
			Name="Index"
			Visible=true
			Group="ID"
			InheritedFrom="thread"
		#tag EndViewProperty
		#tag ViewProperty
			Name="Left"
			Visible=true
			Group="Position"
			Type="Integer"
			InheritedFrom="thread"
		#tag EndViewProperty
		#tag ViewProperty
			Name="Name"
			Visible=true
			Group="ID"
			InheritedFrom="thread"
		#tag EndViewProperty
		#tag ViewProperty
			Name="Priority"
			Visible=true
			Group="Behavior"
			InitialValue="5"
			Type="Integer"
			InheritedFrom="thread"
		#tag EndViewProperty
		#tag ViewProperty
			Name="StackSize"
			Visible=true
			Group="Behavior"
			InitialValue="0"
			Type="Integer"
			InheritedFrom="thread"
		#tag EndViewProperty
		#tag ViewProperty
			Name="Super"
			Visible=true
			Group="ID"
			InheritedFrom="thread"
		#tag EndViewProperty
		#tag ViewProperty
			Name="Top"
			Visible=true
			Group="Position"
			Type="Integer"
			InheritedFrom="thread"
		#tag EndViewProperty
	#tag EndViewBehavior
End Class
#tag EndClass
